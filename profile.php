<?php
session_start();
include 'db.php';
requireLogin();
$uid  = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$uid"));
$role = $user['role'];

$error = $success = '';

// ── Handle avatar upload
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error']===0){
  $file     = $_FILES['avatar'];
  $ext      = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
  $allowed  = ['jpg','jpeg','png','gif','webp'];
  $max_size = 3 * 1024 * 1024;
  if(!in_array($ext,$allowed)){
    $error = 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP.';
  } elseif($file['size'] > $max_size){
    $error = 'File too large. Maximum size is 3MB.';
  } else {
    $dir = 'uploads/avatars/';
    if(!is_dir($dir)) mkdir($dir, 0755, true);
    if($user['avatar'] && file_exists($user['avatar'])) unlink($user['avatar']);
    $filename = $dir.'user_'.$uid.'_'.time().'.'.$ext;
    if(move_uploaded_file($file['tmp_name'],$filename)){
      $conn->query("UPDATE users SET avatar='$filename' WHERE id=$uid");
      $user['avatar'] = $filename;
      $_SESSION['flash_success']='Profile picture updated! ✅';
      header("Location: profile.php"); exit();
    } else {
      $error = 'Upload failed. Check folder permissions.';
    }
  }
}

// ── Handle profile update
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])){
  $name         = trim($_POST['name']         ?? '');
  $email        = trim($_POST['email']        ?? '');
  $bio          = trim($_POST['bio']          ?? '');
  $location     = trim($_POST['location']     ?? '');
  $university   = trim($_POST['university']   ?? '');
  $company_name = trim($_POST['company_name'] ?? '');
  $skills       = trim($_POST['skills']       ?? '');
  $github       = trim($_POST['github']       ?? '');
  $linkedin     = trim($_POST['linkedin']     ?? '');
  $portfolio    = trim($_POST['portfolio']    ?? '');

  if(!$name || !$email){ $error='Name and email are required.'; }
  elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){ $error='Invalid email address.'; }
  else {
    $stmt=$conn->prepare("UPDATE users SET name=?,email=?,bio=?,location=?,university=?,company_name=?,skills=?,github=?,linkedin=?,portfolio=? WHERE id=?");
    $stmt->bind_param("ssssssssssi",$name,$email,$bio,$location,$university,$company_name,$skills,$github,$linkedin,$portfolio,$uid);
    if($stmt->execute()){
      $_SESSION['user_name']=$name;
      $user=array_merge($user,compact('name','email','bio','location','university','company_name','skills','github','linkedin','portfolio'));
      $success='Profile updated! ✅';
    } else { $error='Update failed: '.$conn->error; }
  }
}

$reviews = mysqli_query($conn,
  "SELECT r.*,u.name as reviewer_name,t.title as task_title
   FROM reviews r JOIN users u ON r.reviewer_id=u.id JOIN tasks t ON r.task_id=t.id
   WHERE r.reviewee_id=$uid ORDER BY r.created_at DESC LIMIT 10");
$avg_r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(AVG(rating),0) as a FROM reviews WHERE reviewee_id=$uid"))['a'];

// Stats for candidate
$total_earned = 0; $completed_count = 0;
$completed_tasks = [];
if($role==='candidate'){
  $total_earned    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as c FROM payments WHERE candidate_id=$uid AND status='released'"))['c'];
  $completed_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM applications WHERE candidate_id=$uid AND status='completed'"))['c'];
  // Fetch completed task titles for My Gig popup
  $gig_q = mysqli_query($conn,"SELECT t.title, t.category, a.id as app_id FROM applications a JOIN tasks t ON t.id=a.task_id WHERE a.candidate_id=$uid AND a.status='completed' ORDER BY a.applied_at DESC");
  while($g = $gig_q->fetch_assoc()) $completed_tasks[] = $g;
}

$universities = [

  // ════════════════════════════════════════════════════════════
  //  I N D I A
  // ════════════════════════════════════════════════════════════

  // ── IITs (Indian Institutes of Technology) ──
  "IIT Bombay","IIT Delhi","IIT Madras","IIT Kanpur","IIT Kharagpur","IIT Roorkee","IIT Guwahati",
  "IIT Hyderabad","IIT Indore","IIT Jodhpur","IIT Mandi","IIT Patna","IIT Tirupati","IIT Varanasi (IIT-BHU)",
  "IIT Bhubaneswar","IIT Gandhinagar","IIT Jammu","IIT Palakkad","IIT Dharwad","IIT Bhilai","IIT Goa",
  "IIT Dhanbad (ISM)","IIT Kharagpur (Extension)","IIT Ropar","IIT Srinagar",

  // ── IIMs (Indian Institutes of Management) ──
  "IIM Ahmedabad","IIM Bangalore","IIM Calcutta","IIM Lucknow","IIM Kozhikode","IIM Indore","IIM Shillong",
  "IIM Udaipur","IIM Raipur","IIM Ranchi","IIM Trichy","IIM Kashipur","IIM Rohtak","IIM Nagpur",
  "IIM Amritsar","IIM Bodh Gaya","IIM Jammu","IIM Sambalpur","IIM Sirmaur","IIM Visakhapatnam",
  "IIM Mumbai",

  // ── IISc & IISERs ──
  "IISc Bangalore","IISER Pune","IISER Kolkata","IISER Mohali","IISER Bhopal","IISER Thiruvananthapuram",
  "IISER Tirupati","IISER Berhampur",

  // ── BITS Pilani Group ──
  "BITS Pilani","BITS Goa","BITS Hyderabad","BITS Dubai",

  // ── NITs (National Institutes of Technology) ──
  "NIT Trichy","NIT Surathkal","NIT Warangal","NIT Calicut","NIT Rourkela","NIT Allahabad (MNNIT)",
  "NIT Nagpur (VNIT)","NIT Durgapur","NIT Kurukshetra","NIT Jamshedpur","NIT Silchar","NIT Surat (SVNIT)",
  "NIT Hamirpur","NIT Jalandhar","NIT Patna","NIT Agartala","NIT Manipur","NIT Meghalaya","NIT Mizoram",
  "NIT Nagaland","NIT Sikkim","NIT Arunachal Pradesh","NIT Goa","NIT Puducherry","NIT Raipur","NIT Delhi",
  "NIT Srinagar","NIT Uttarakhand","NIT Andhra Pradesh",

  // ── IIITs (Indian Institutes of Information Technology) ──
  "IIIT Hyderabad","IIIT Bangalore","IIIT Delhi","IIIT Allahabad","IIIT Pune","IIIT Jabalpur","IIIT Gwalior",
  "IIIT Lucknow","IIIT Kota","IIIT Vadodara","IIIT Surat","IIIT Dharwad","IIIT Kancheepuram","IIIT Ranchi",
  "IIIT Nagpur","IIIT Pune II","IIIT Senapati","IIIT Sri City","IIIT Sonepat","IIIT Una",
  "IIIT Tiruchirappalli","IIIT Bhagalpur","IIIT Bhopal","IIIT Raichur","IIIT Agartala",
  "IIIT Kalyani","IIIT Manipur","IIIT Kilohrad",

  // ── Central Universities ──
  "Delhi University (DU)","Jawaharlal Nehru University (JNU)","University of Hyderabad",
  "Allahabad University (UGSC)","Banaras Hindu University (BHU)","Aligarh Muslim University (AMU)",
  "Jamia Millia Islamia","Jadavpur University","Anna University","Panjab University Chandigarh",
  "Pondicherry University","Tezpur University","Sikkim University","North-Eastern Hill University (NEHU)",
  "Manipur University","Mizoram University","Nagaland University","Assam University","Tripura University",
  "Hemvati Nandan Bahuguna Garhwal University","Central University of Bihar","Central University of Gujarat",
  "Central University of Haryana","Central University of Himachal Pradesh","Central University of Jammu",
  "Central University of Jharkhand","Central University of Karnataka","Central University of Kashmir",
  "Central University of Kerala","Central University of Odisha","Central University of Punjab",
  "Central University of Rajasthan","Central University of South Bihar","Central University of Tamil Nadu",
  "Dr. Harisingh Gour University (Sagar)","Guru Ghasidas Vishwavidyalaya","Indira Gandhi National Tribal University",
  "Mahatma Gandhi Antarrashtriya Hindi Vishwavidyalaya","Maulana Azad National Urdu University",
  "Rajiv Gandhi University (Arunachal Pradesh)","Babasaheb Bhimrao Ambedkar University (Lucknow)",

  // ── VIT Group ──
  "VIT Vellore","VIT Chennai","VIT Bhopal","VIT AP","VIT Amaravati",

  // ── Amity University Group ──
  "Amity University Noida","Amity University Mumbai","Amity University Jaipur","Amity University Lucknow",
  "Amity University Gurgaon","Amity University Patna","Amity University Kolkata","Amity University Gwalior",
  "Amity University Ranchi","Amity University Chhattisgarh","Amity University Madhya Pradesh",

  // ── SRM Group ──
  "SRM Institute of Science and Technology (Chennai)","SRM University AP","SRM University Delhi-NCR",
  "SRM Institute Kattankulathur","SRM University Gangtok",

  // ── Manipal Group ──
  "Manipal Academy of Higher Education (MAHE)","Manipal University Jaipur","Manipal University Bhubaneswar",
  "Sikkim Manipal University","Manipal College of Medical Sciences Pokhara",

  // ── Symbiosis Group ──
  "Symbiosis International University (Pune)","Symbiosis Institute of Business Management",
  "Symbiosis Institute of Technology","Symbiosis Institute of Media and Communication",
  "Symbiosis Institute of Computer Studies and Research","Symbiosis Centre for Management and HRD",

  // ── Deemed & Private Universities — Maharashtra ──
  "Mumbai University","Pune University (SPPU)","Nagpur University (RTMNU)",
  "College of Engineering Pune (COEP)","Pune Institute of Computer Technology (PICT)",
  "DJ Sanghvi College of Engineering","VJTI Mumbai","KJ Somaiya College of Engineering",
  "Sardar Patel College of Engineering","Institute of Chemical Technology (ICT) Mumbai",
  "NMIMS Mumbai","Narsee Monjee College of Commerce and Economics",
  "Jai Hind College Mumbai","Sophia College Mumbai","St. Xavier's College Mumbai",
  "Fergusson College Pune","Modern College Pune","MIT College of Engineering Pune",
  "Vishwakarma Institute of Technology","Sinhgad College of Engineering",
  "Bharati Vidyapeeth University","D.Y. Patil University",

  // ── Karnataka ──
  "Bangalore University","Mysore University","Mangalore University","Visvesvaraya Technological University (VTU)",
  "RV College of Engineering","PES University","MS Ramaiah Institute of Technology",
  "BMS College of Engineering","Dayananda Sagar University","REVA University",
  "Nitte University","Manipal Institute of Technology Manipal","Christ University Bangalore",
  "JAIN University Bangalore","M.S. Ramaiah University of Applied Sciences","Alliance University",
  "Presidency University Bangalore","CMR University","GITAM University Bangalore",
  "KLE Technological University","SDM College of Engineering",
  "Acharya Institute of Technology","New Horizon College of Engineering",

  // ── Tamil Nadu ──
  "Madras University","Bharathiar University","Annamalai University","Madurai Kamaraj University",
  "Bharathidasan University","Periyar University","Manonmaniam Sundaranar University",
  "Alagappa University","Thiruvalluvar University","Mother Teresa Women's University",
  "Tamil Nadu Agricultural University","Amrita Vishwa Vidyapeetham","SASTRA University",
  "SRM Valliammai Engineering College","PSG College of Technology",
  "Coimbatore Institute of Technology","Sri Sivasubramaniya Nadar College of Engineering",
  "SSN College of Engineering","Vellore Institute of Technology (VIT)","Karpagam Academy",
  "Saveetha University","Vel Tech University","Sathyabama University","Hindustan University",
  "Sri Ramachandra University","Dr. MGR University","Chettinad Academy of Research and Education",
  "Kalasalingam Academy","KPR Institute of Engineering","RMK Engineering College",

  // ── Andhra Pradesh & Telangana ──
  "JNTU Hyderabad","Osmania University","Andhra University","Sri Krishnadevaraya University",
  "JNTU Kakinada","JNTU Anantapur","Acharya Nagarjuna University","Krishna University",
  "GITAM University Visakhapatnam","Koneru Lakshmaiah Education Foundation (KL University)",
  "Vignan University","VIT-AP University","Mahindra University Hyderabad",
  "University of Hyderabad","Sreenidhi Institute of Science and Technology",
  "CBIT Hyderabad","Vasavi College of Engineering","CVR College of Engineering",
  "Malla Reddy University","Anurag University",

  // ── Kerala ──
  "Kerala University","Calicut University","Cochin University of Science and Technology (CUSAT)",
  "Mahatma Gandhi University Kerala","Kannur University","APJ Abdul Kalam Technological University (KTU)",
  "Sree Chitra Tirunal Institute","National Institute of Technology Calicut",
  "College of Engineering Thiruvananthapuram (CET)","Model Engineering College",
  "Mar Baselios College of Engineering","Amrita School of Engineering Coimbatore","Rajagiri College",

  // ── Gujarat ──
  "Gujarat University","Sardar Patel University","Saurashtra University","South Gujarat University",
  "Maharaja Sayajirao University of Baroda","Nirma University","Dhirubhai Ambani Institute (DAIICT)",
  "PDPU Gandhinagar","Anand Agricultural University","Gujarat Technological University (GTU)",
  "Charotar University of Science and Technology (CHARUSAT)","Pandit Deendayal Energy University",

  // ── Rajasthan ──
  "University of Rajasthan","MDS University Ajmer","Jodhpur National University","Jai Narain Vyas University",
  "Rajasthan Technical University (RTU)","Bikaner Technical University","Malaviya National Institute of Technology Jaipur",
  "LNMIIT Jaipur","MNIT Jaipur","Banasthali Vidyapith","ICFAI University Jaipur",
  "Jaipur National University","Poornima University","Manipal University Jaipur",
  "Suresh Gyan Vihar University","Amity University Jaipur",

  // ── Punjab & Haryana ──
  "Panjab University Chandigarh","Guru Nanak Dev University Amritsar","Thapar Institute (Patiala)",
  "Chandigarh University","Lovely Professional University (LPU)","Punjabi University Patiala",
  "Punjab Technical University (IKG PTU)","Chitkara University Punjab","UIET Chandigarh",
  "Kurukshetra University","MDU Rohtak","Haryana Agricultural University","Deenbandhu Chhotu Ram University",
  "GJU Hisar","Shoolini University","Jaypee University Waknaghat",

  // ── Himachal Pradesh & J&K ──
  "Himachal Pradesh University","National Institute of Technology Hamirpur","Jaypee University Solan",
  "Chitkara University Himachal","APG Shimla University","University of Jammu","University of Kashmir",
  "NIT Srinagar","SMVDU Katra","Cluster University Srinagar",

  // ── Uttarakhand ──
  "Kumaun University","Garhwal University (HNB)","Graphic Era University","DIT University",
  "Uttarakhand Technical University (UTU)","University of Petroleum & Energy Studies (UPES)",
  "Quantum University","Dev Sanskriti Vishwavidyalaya","Shri Dev Suman Uttarakhand University",

  // ── Uttar Pradesh ──
  "Lucknow University","Agra University (DBRAU)","Gorakhpur University","Kanpur University (CSJM)",
  "Allahabad University","BHU Varanasi","AMU Aligarh","Jamia Hamdard Delhi Campus",
  "Chaudhary Charan Singh University Meerut","UPTU (Dr. APJ Abdul Kalam Technical University)",
  "GLA University Mathura","Integral University Lucknow","Teerthanker Mahaveer University",
  "Amity University Lucknow","PSIT Kanpur","Invertis University","Sharda University",
  "Galgotias University","Noida International University","G.L. Bajaj Institute",
  "Harcourt Butler Technical University","AKTU Lucknow","Bundelkhand University",

  // ── Bihar & Jharkhand ──
  "Patna University","Magadh University","Bhagalpur University","BNMU Madhepura",
  "Lalit Narayan Mithila University","Munger University","Central University of South Bihar",
  "NIT Patna","NIFT Patna","Ranchi University","Birsa Agricultural University","Vinoba Bhave University",
  "Kolhan University","Sido Kanhu Murmu University","NIT Jamshedpur",

  // ── West Bengal ──
  "Calcutta University","Jadavpur University","Rabindra Bharati University","Burdwan University",
  "Vidyasagar University","North Bengal University","Kalyani University","Presidency University Kolkata",
  "Heritage Institute of Technology","IIEST Shibpur","Maulana Abul Kalam Azad University of Technology (MAKAUT)",
  "Techno India University","Sister Nivedita University","Adamas University","KIIT University (Bhubaneswar)",

  // ── Odisha ──
  "Utkal University","Sambalpur University","Berhampur University","Fakir Mohan University",
  "NIT Rourkela","Silicon Institute of Technology","ITER Bhubaneswar","KIIT University",
  "SOA University (Siksha O Anusandhan)","Centurion University",

  // ── Madhya Pradesh ──
  "Barkatullah University Bhopal","Devi Ahilya Vishwavidyalaya Indore","Jiwaji University Gwalior",
  "Vikram University Ujjain","Sagar University (Dr. Hari Singh Gour)","MANIT Bhopal",
  "IIT Indore","IIITM Gwalior","Shri Gobind Singh Engineering College","Truba College",
  "Rabindranath Tagore University","Amity University Gwalior","Jagran Lakecity University",
  "Oriental University Indore","Medi-Caps University",

  // ── Chhattisgarh ──
  "Pt. Ravishankar Shukla University","NIT Raipur","CSVTU Bhilai","Kalinga University",
  "Hemchand Yadav University","IIIT Naya Raipur",

  // ── Assam & Northeast ──
  "Dibrugarh University","Gauhati University","Cotton University","Assam Engineering College",
  "NIT Silchar","Tezpur University","NEHU Shillong","Manipur University","Mizoram University",
  "Nagaland University","Tripura University","Rajiv Gandhi University Arunachal",
  "Assam Don Bosco University","Royal Global University","Kaziranga University",

  // ── Goa ──
  "Goa University","NIT Goa","Goa College of Engineering","Don Bosco College of Engineering Goa",

  // ── Delhi NCR ──
  "Delhi University (DU)","Jamia Millia Islamia","JNU Delhi","IGNOU",
  "Guru Gobind Singh Indraprastha University (GGSIPU)","Netaji Subhas University of Technology (NSUT)",
  "Delhi Technological University (DTU)","Indraprastha College for Women","Miranda House Delhi",
  "Hindu College Delhi","Lady Shri Ram College Delhi","Kirori Mal College Delhi",
  "Sri Venkateswara College Delhi","Hansraj College Delhi","Ramjas College Delhi",
  "Dyal Singh College Delhi","IIFT Delhi","IMT Ghaziabad","BML Munjal University",
  "Amity University Noida","Sharda University","Galgotias University","Noida International University",
  "Manav Rachna University","Ashoka University","Plaksha University (Mohali)",

  // ── Deemed & Private (Pan-India) ──
  "O.P. Jindal Global University","Shiv Nadar University","FLAME University Pune",
  "Azim Premji University Bangalore","Krea University","Ahmedabad University",
  "CEPT University Ahmedabad","Nirma University Ahmedabad","Pandit Deendayal Energy University",
  "MIT World Peace University Pune","Symbiosis Skills and Professional University",
  "UPES Dehradun","Graphic Era Deemed University","DIT University Dehradun",
  "Sage University Bhopal","Vivekananda Global University Jaipur",

  // ── Business Schools / Management ──
  "XLRI Jamshedpur","ISB Hyderabad","ISB Mohali","SP Jain Mumbai","FMS Delhi",
  "MDI Gurgaon","IMT Ghaziabad","IMI Delhi","XIMB Bhubaneswar","XIM University",
  "TAPMI Manipal","IRMA Anand","TISS Mumbai","TISS Hyderabad","TISS Guwahati","TISS Tuljapur",
  "Great Lakes Institute of Management","Fore School of Management","LBSIM Delhi",
  "Narsee Monjee Institute of Management Studies","KJ Somaiya Institute of Management",
  "SIES School of Management","Welingkar Institute of Management",
  "International Management Institute Bhubaneswar","BIMTECH Greater Noida",
  "Birla Institute of Management Technology",

  // ── Law Schools ──
  "National Law School of India University (NLSIU) Bangalore",
  "National Academy of Legal Studies and Research (NALSAR) Hyderabad",
  "National Law University Delhi","National Law University Jodhpur",
  "Gujarat National Law University (GNLU)","Symbiosis Law School Pune",
  "ILS Law College Pune","Faculty of Law Delhi University",
  "Amity Law School","Jindal Global Law School",

  // ── Design Schools ──
  "National Institute of Design (NID) Ahmedabad","NID Gandhinagar","NID Jorhat",
  "NID Bhopal","NID Amaravati","NID Jorhat","NID Kurukshetra","NID Jorhat",
  "National Institute of Fashion Technology (NIFT) Delhi","NIFT Mumbai","NIFT Chennai",
  "NIFT Bangalore","NIFT Kolkata","NIFT Hyderabad","NIFT Bhopal","NIFT Jodhpur",
  "NIFT Gandhinagar","NIFT Patna","NIFT Raebareli","NIFT Shillong","NIFT Srinagar",
  "Industrial Design Centre (IDC) IIT Bombay","MIT Institute of Design Pune",
  "Symbiosis Institute of Design","Raffles Design International",
  "Pearl Academy Delhi","JD Institute of Fashion Technology",

  // ── Medical Universities ──
  "AIIMS Delhi","AIIMS Bhopal","AIIMS Bhubaneswar","AIIMS Jodhpur","AIIMS Patna",
  "AIIMS Raipur","AIIMS Rishikesh","AIIMS Nagpur","AIIMS Kalyani","AIIMS Bathinda",
  "AIIMS Gorakhpur","AIIMS Bibinagar","AIIMS Madurai","AIIMS Rajkot","AIIMS Awantipora",
  "Maulana Azad Medical College Delhi","KMC Manipal","Kasturba Medical College",
  "Amrita Institute of Medical Sciences","Sri Ramachandra Medical College",
  "PSG Institute of Medical Sciences","JIPMER Puducherry","CMC Vellore","CMC Ludhiana",
  "Grant Medical College Mumbai","Seth GS Medical College Mumbai",
  "KEM Hospital Mumbai","BJ Medical College Ahmedabad","SMS Medical College Jaipur",

  // ════════════════════════════════════════════════════════════
  //  U S A
  // ════════════════════════════════════════════════════════════

  // ── Ivy League ──
  "Harvard University","Yale University","Princeton University","Columbia University",
  "University of Pennsylvania","Brown University","Dartmouth College","Cornell University",

  // ── Top Research / Tech ──
  "MIT (Massachusetts Institute of Technology)","Stanford University","Caltech",
  "Carnegie Mellon University","University of Chicago","Johns Hopkins University",
  "Northwestern University","Duke University","Vanderbilt University","Rice University",
  "University of Notre Dame","Georgetown University","Emory University","Tufts University",
  "Boston College","Wake Forest University","Case Western Reserve University",
  "Tulane University","Lehigh University","Brandeis University","University of Rochester",
  "RPI (Rensselaer Polytechnic Institute)","Worcester Polytechnic Institute","Drexel University",
  "Stevens Institute of Technology","New Jersey Institute of Technology",

  // ── Top Public Universities ──
  "University of California Berkeley (UC Berkeley)","UCLA","UC San Diego (UCSD)","UC Davis",
  "UC Santa Barbara (UCSB)","UC Irvine","UC Santa Cruz","UC Riverside","UC Merced",
  "University of Michigan Ann Arbor","University of Virginia","University of North Carolina Chapel Hill",
  "University of Wisconsin-Madison","Georgia Tech (Georgia Institute of Technology)",
  "University of Texas at Austin","University of Illinois Urbana-Champaign (UIUC)",
  "University of Washington","Ohio State University","Purdue University",
  "Penn State University","University of Maryland College Park","University of Southern California (USC)",
  "New York University (NYU)","Boston University","Arizona State University","University of Arizona",
  "University of Colorado Boulder","Indiana University Bloomington","Rutgers University",
  "University of Pittsburgh","University of Minnesota","University of Florida","University of Georgia",
  "University of Connecticut","Stony Brook University (SUNY)","University at Buffalo (SUNY)",
  "University of Cincinnati","University of Iowa","Iowa State University","Kansas State University",
  "University of Kansas","University of Missouri","University of Nebraska","University of Oklahoma",
  "Oklahoma State University","University of Tennessee","University of Kentucky","University of Alabama",
  "Auburn University","Mississippi State University","Louisiana State University",
  "University of South Carolina","Clemson University","University of Utah","University of Oregon",
  "Oregon State University","Washington State University","University of Hawaii Manoa",
  "New Mexico State University","University of New Mexico","Colorado State University",
  "Montana State University","University of Idaho","University of Wyoming","University of Vermont",
  "University of Maine","University of New Hampshire","University of Rhode Island",
  "University of Delaware","University of South Florida","Florida State University",
  "Florida International University","University of Central Florida","University of North Florida",
  "Virginia Tech","Virginia Commonwealth University","George Mason University",
  "George Washington University","American University","Howard University","Gallaudet University",

  // ── Liberal Arts ──
  "Williams College","Amherst College","Swarthmore College","Wellesley College","Bowdoin College",
  "Middlebury College","Pomona College","Carleton College","Haverford College","Claremont McKenna College",
  "Grinnell College","Vassar College","Colby College","Bates College","Hamilton College",
  "Smith College","Mount Holyoke College","Barnard College","Trinity College Hartford",
  "Wesleyan University","Lafayette College","Colgate University","Bucknell University",
  "Dickinson College","Skidmore College","Union College","Gettysburg College",

  // ── Other Notable US ──
  "Syracuse University","Fordham University","Marquette University","Loyola University Chicago",
  "DePaul University","Xavier University","St. Louis University","Creighton University",
  "Santa Clara University","University of San Diego","Pepperdine University",
  "Chapman University","Yeshiva University","Pace University","Hofstra University",
  "Long Island University","Adelphi University","Seton Hall University","Fairfield University",
  "Providence College","Villanova University","Duquesne University",

  // ════════════════════════════════════════════════════════════
  //  U N I T E D   K I N G D O M
  // ════════════════════════════════════════════════════════════

  "University of Oxford","University of Cambridge","Imperial College London",
  "London School of Economics (LSE)","UCL (University College London)",
  "King's College London","University of Edinburgh","University of Manchester",
  "University of Warwick","Durham University","University of Bristol",
  "University of St Andrews","University of Glasgow","University of Birmingham",
  "University of Leeds","University of Sheffield","University of Nottingham",
  "University of Southampton","University of Liverpool","University of Exeter",
  "Cardiff University","Queen's University Belfast","University of Aberdeen",
  "University of Dundee","University of Strathclyde","University of Reading",
  "Queen Mary University of London","Royal Holloway University of London",
  "University of Sussex","Loughborough University","Newcastle University",
  "University of Bath","University of Surrey","Lancaster University","University of Leicester",
  "University of East Anglia","Heriot-Watt University","University of York",
  "Aston University","Brunel University London","City University of London","Coventry University",
  "De Montfort University","Goldsmiths University of London","Kingston University",
  "London Metropolitan University","Middlesex University","Northumbria University",
  "Oxford Brookes University","Plymouth University","University of Portsmouth",
  "Sheffield Hallam University","University of West England (UWE)","Ulster University",
  "Swansea University","Aberystwyth University","Bangor University",
  "Keele University","University of Hull","University of Lincoln","University of Huddersfield",
  "University of Derby","University of Chester","University of Salford","University of Bradford",
  "Leeds Beckett University","Manchester Metropolitan University","Liverpool John Moores University",
  "Birmingham City University","Nottingham Trent University","Anglia Ruskin University",
  "University of Greenwich","University of Westminster","University of East London",
  "Teesside University","University of Bolton","University of Bedfordshire",
  "London South Bank University","University of Northampton","University of Wolverhampton",

  // ════════════════════════════════════════════════════════════
  //  C A N A D A
  // ════════════════════════════════════════════════════════════

  "University of Toronto","McGill University","University of British Columbia (UBC)",
  "University of Waterloo","Western University","University of Alberta","McMaster University",
  "Queen's University Canada","Dalhousie University","University of Ottawa","Simon Fraser University",
  "York University","University of Calgary","University of Manitoba","University of Guelph",
  "University of Victoria","Carleton University","Concordia University","Ryerson University (Toronto Metropolitan)",
  "Wilfrid Laurier University","Brock University","Memorial University of Newfoundland",
  "University of New Brunswick","University of Regina","University of Saskatchewan",
  "Saint Mary's University Halifax","Acadia University","Mount Allison University",
  "UQAM (Université du Québec à Montréal)","Université de Montréal","Université Laval",
  "Polytechnique Montréal","HEC Montréal","Université de Sherbrooke","Université du Québec",
  "University of Prince Edward Island","Cape Breton University","Lakehead University",
  "Laurentian University","Nipissing University","Algoma University","Thompson Rivers University",
  "University of the Fraser Valley","Kwantlen Polytechnic University","BCIT",
  "NAIT (Northern Alberta Institute of Technology)","SAIT","Seneca College",
  "Humber College","George Brown College","Centennial College","Sheridan College",

  // ════════════════════════════════════════════════════════════
  //  A U S T R A L I A  &  N E W  Z E A L A N D
  // ════════════════════════════════════════════════════════════

  "University of Melbourne","Australian National University (ANU)","University of Sydney",
  "University of Queensland","Monash University","UNSW Sydney","University of Adelaide",
  "University of Western Australia","Macquarie University","Deakin University",
  "RMIT University","Curtin University","University of Newcastle","University of Wollongong",
  "James Cook University","Griffith University","Murdoch University","Flinders University",
  "University of Technology Sydney (UTS)","La Trobe University","Swinburne University of Technology",
  "University of South Australia","Bond University","Charles Darwin University",
  "Federation University Australia","CQUniversity","Southern Cross University","Edith Cowan University",
  "University of the Sunshine Coast","Charles Sturt University","Western Sydney University",
  "University of Canberra","Australian Catholic University",
  // New Zealand
  "University of Auckland","University of Otago","Victoria University of Wellington",
  "Massey University","University of Canterbury","University of Waikato","Lincoln University NZ",
  "Auckland University of Technology (AUT)","Eastern Institute of Technology","Unitec New Zealand",

  // ════════════════════════════════════════════════════════════
  //  E U R O P E
  // ════════════════════════════════════════════════════════════

  // Germany
  "Technical University of Munich (TUM)","LMU Munich","Heidelberg University","Humboldt University of Berlin",
  "Freie Universität Berlin","RWTH Aachen University","Karlsruhe Institute of Technology (KIT)",
  "University of Stuttgart","University of Frankfurt","University of Hamburg",
  "Dresden University of Technology (TU Dresden)","University of Cologne","University of Bonn",
  "University of Mannheim","University of Tübingen","University of Freiburg",
  "Bielefeld University","University of Göttingen","Leibniz University Hannover",
  "TU Berlin","TU Dortmund","University of Duisburg-Essen","University of Münster",
  "University of Bayreuth","University of Potsdam","University of Kiel","University of Greifswald",
  "University of Halle-Wittenberg","University of Leipzig","University of Jena",
  "University of Konstanz","University of Ulm","University of Siegen","University of Marburg",
  "University of Mainz (JGU)","TU Darmstadt","University of Würzburg","University of Erlangen-Nuremberg (FAU)",
  "University of Regensburg","University of Passau","University of Augsburg","FH Münster",

  // Netherlands
  "Delft University of Technology (TU Delft)","University of Amsterdam","Leiden University",
  "Erasmus University Rotterdam","Utrecht University","Eindhoven University of Technology (TU/e)",
  "Wageningen University","University of Groningen","Radboud University","Maastricht University",
  "VU Amsterdam","Tilburg University","University of Twente","Open Universiteit Netherlands",

  // Switzerland
  "ETH Zurich","EPFL (Lausanne)","University of Zurich","University of Bern",
  "University of Geneva","University of Basel","University of Lausanne","University of St. Gallen (HSG)",
  "University of Fribourg","University of Lucerne","University of Lugano (USI)",

  // France
  "Sorbonne University","École Polytechnique","Sciences Po Paris","HEC Paris",
  "CentraleSupélec","École Normale Supérieure Paris","ESSEC Business School","INSEAD",
  "Grenoble École de Management","EM Lyon Business School","Université Paris-Saclay",
  "Université PSL","Pierre and Marie Curie University","Université Paris-Dauphine",
  "Université Paul Sabatier Toulouse III","Aix-Marseille University","Université de Strasbourg",
  "Université de Bordeaux","Université de Lille","Université de Nantes","Université de Lyon",
  "INSA Lyon","INSA Toulouse","École des Mines de Paris","Institut Polytechnique de Paris",
  "Telecom Paris","EDHEC Business School","Audencia Business School","KEDGE Business School",

  // Belgium
  "KU Leuven","Ghent University","Université Libre de Bruxelles (ULB)",
  "Vrije Universiteit Brussel (VUB)","University of Antwerp","UCLouvain",
  "Hasselt University","University of Liège","University of Namur",

  // Scandinavia
  "University of Copenhagen","Aarhus University","Technical University of Denmark (DTU)",
  "Copenhagen Business School","Lund University","Uppsala University","Stockholm University",
  "Chalmers University of Technology","KTH Royal Institute of Technology",
  "Linköping University","University of Helsinki","Aalto University","University of Oslo",
  "Norwegian University of Science and Technology (NTNU)","University of Bergen",
  "University of Tromsø","BI Norwegian Business School","Umeå University",
  "University of Gothenburg","Örebro University","Mälardalen University",
  "Tampere University","University of Turku","University of Oulu","University of Eastern Finland",
  "IT University of Copenhagen","Roskilde University",

  // Italy
  "University of Bologna","Sapienza University of Rome","Politecnico di Milano",
  "University of Turin","University of Padua","Scuola Normale Superiore",
  "Politecnico di Torino","University of Milan","University of Florence","University of Pisa",
  "Bocconi University","University of Naples Federico II","University of Genoa",
  "University of Catania","University of Palermo","University of Trento",
  "Free University of Bozen-Bolzano","IMT School for Advanced Studies Lucca",
  "University of Siena","University of Brescia","University of Bergamo",

  // Spain
  "University of Barcelona","Autonomous University of Madrid (UAM)",
  "Complutense University of Madrid","University of Valencia","Polytechnic University of Catalonia (UPC)",
  "University of Navarra","Carlos III University of Madrid (UC3M)","IE University",
  "ESADE Business School","IESE Business School","EAE Business School",
  "Pompeu Fabra University","Autonomous University of Barcelona","University of Granada",
  "University of Seville","University of Salamanca","University of Malaga","University of Zaragoza",
  "University of Santiago de Compostela","University of the Basque Country",

  // Portugal
  "University of Lisbon","Technical University of Lisbon (IST)","NOVA University Lisbon",
  "University of Porto","University of Coimbra","University of Minho","University of Aveiro",
  "Catholic University of Portugal","ISCTE Business School",

  // Austria
  "University of Vienna","Vienna University of Technology (TU Wien)","Graz University of Technology",
  "University of Graz","Vienna University of Economics and Business (WU Wien)",
  "University of Innsbruck","University of Linz","Danube University Krems",

  // Ireland
  "University College Dublin (UCD)","Trinity College Dublin","University College Cork (UCC)",
  "University of Galway (NUIG)","Dublin City University (DCU)","University of Limerick",
  "Maynooth University","Dublin Institute of Technology","Technological University Dublin",
  "RCSI University of Medicine",

  // Eastern Europe
  "Charles University Prague","Czech Technical University (CTU)","Masaryk University Brno",
  "Brno University of Technology","University of West Bohemia","Palacký University Olomouc",
  "Warsaw University","University of Warsaw (UW)","AGH University of Science and Technology Krakow",
  "Jagiellonian University Krakow","Warsaw University of Technology","Poznan University of Technology",
  "Wroclaw University of Technology","Gdansk University of Technology","University of Lodz",
  "Budapest University of Technology and Economics (BME)","Eötvös Loránd University (ELTE)",
  "Corvinus University Budapest","University of Debrecen","University of Pécs","University of Szeged",
  "University of Bucharest","Polytechnic University of Bucharest (UPB)","Babeș-Bolyai University",
  "Alexandru Ioan Cuza University","University of Ljubljana","University of Maribor",
  "University of Zagreb","University of Split","University of Belgrade","University of Novi Sad",
  "Vilnius University","Vilnius Gediminas Technical University","University of Latvia (LU)",
  "Riga Technical University","University of Tartu","Tallinn University of Technology (TalTech)",
  "Tallinn University","University of Sofia","Technical University of Sofia",
  "Comenius University Bratislava","Slovak University of Technology",
  "University of Lodz","Lodz University of Technology","Silesian University of Technology",

  // Russia & Central Asia
  "Lomonosov Moscow State University (MSU)","Moscow Institute of Physics and Technology (MIPT)",
  "Saint Petersburg State University","Bauman Moscow State Technical University",
  "HSE University Moscow","Novosibirsk State University","Ural Federal University",
  "Tomsk State University","Tomsk Polytechnic University","ITMO University",
  "National Research University MEPhI","Moscow Aviation Institute (MAI)",
  "Nazarbayev University Kazakhstan","Astana IT University",
  "National University of Uzbekistan","Turin Polytechnic University in Tashkent",

  // ════════════════════════════════════════════════════════════
  //  A S I A
  // ════════════════════════════════════════════════════════════

  // Singapore
  "National University of Singapore (NUS)","Nanyang Technological University (NTU)",
  "Singapore Management University (SMU)","Singapore University of Technology and Design (SUTD)",
  "Singapore Institute of Technology","SIM University","James Cook University Singapore",
  "INSEAD Asia Campus",

  // Hong Kong
  "University of Hong Kong (HKU)","Hong Kong University of Science and Technology (HKUST)",
  "Chinese University of Hong Kong (CUHK)","Hong Kong Polytechnic University (PolyU)",
  "City University of Hong Kong (CityU)","Hong Kong Baptist University",
  "Lingnan University Hong Kong","Education University of Hong Kong",

  // China
  "Peking University","Tsinghua University","Fudan University","Zhejiang University",
  "Shanghai Jiao Tong University","Nanjing University","Sun Yat-sen University",
  "Wuhan University","Tongji University","Harbin Institute of Technology (HIT)",
  "Xiamen University","Renmin University of China","Beijing Institute of Technology (BIT)",
  "Beihang University (BUAA)","University of Science and Technology of China (USTC)",
  "Nankai University","Tianjin University","Shandong University","Jilin University",
  "Sichuan University","Central South University","Southeast University","Huazhong University",
  "Northwestern Polytechnical University","Xi'an Jiaotong University",
  "Beijing Normal University","East China Normal University","Beijing Language and Culture University",
  "CUHK (Shenzhen)","Southern University of Science and Technology (SUSTech)",
  "University of Chinese Academy of Sciences (UCAS)","Shanghaitech University",

  // Japan
  "University of Tokyo (UTokyo)","Kyoto University","Osaka University","Tohoku University",
  "Nagoya University","Kyushu University","Hokkaido University","Tokyo Institute of Technology (TIT)",
  "Keio University","Waseda University","Sophia University (Jochi)","Ritsumeikan University",
  "Doshisha University","Kobe University","Hiroshima University","Okayama University",
  "Tsukuba University","Yokohama National University","Tokyo University of Science",
  "Osaka Metropolitan University","Chiba University","Saitama University",

  // South Korea
  "Seoul National University (SNU)","KAIST","POSTECH","Yonsei University","Korea University",
  "Sungkyunkwan University","Hanyang University","Sogang University","Ewha Womans University",
  "UNIST (Ulsan National Institute of Science and Technology)","DGIST","GIST",
  "Kyung Hee University","Chung-Ang University","Konkuk University","Dongguk University",
  "Korea Advanced Institute of Science and Technology (KAIST)","Ajou University",

  // Taiwan
  "National Taiwan University (NTU)","National Taiwan University of Science and Technology (NTUST)",
  "National Cheng Kung University (NCKU)","National Tsing Hua University (NTHU)",
  "National Chiao Tung University (NCTU / NYCU)","National Sun Yat-sen University",
  "National Central University","Academia Sinica","Tunghai University",

  // Malaysia
  "University of Malaya (UM)","Universiti Putra Malaysia (UPM)","Universiti Kebangsaan Malaysia (UKM)",
  "Universiti Teknologi Malaysia (UTM)","Universiti Sains Malaysia (USM)",
  "Universiti Teknologi MARA (UiTM)","INTI International University","Taylor's University",
  "Sunway University","Asia Pacific University","Multimedia University (MMU)",
  "Universiti Utara Malaysia","Universiti Malaysia Sabah","Universiti Malaysia Sarawak",

  // Thailand
  "Chulalongkorn University","Mahidol University","King Mongkut's University of Technology Thonburi",
  "King Mongkut's Institute of Technology Ladkrabang","Thammasat University",
  "Kasetsart University","Chiang Mai University","Prince of Songkla University",
  "Asian Institute of Technology (AIT)",

  // Indonesia
  "University of Indonesia","Bandung Institute of Technology (ITB)",
  "Universitas Gadjah Mada (UGM)","Universitas Airlangga","Universitas Brawijaya",
  "Universitas Padjadjaran","Institut Pertanian Bogor","Universitas Diponegoro",
  "Binus University","Telkom University","Universitas Sebelas Maret","Petra Christian University",

  // Philippines
  "University of the Philippines Diliman","Ateneo de Manila University",
  "De La Salle University","Mapua University","University of Santo Tomas","Lyceum of the Philippines",
  "Polytechnic University of the Philippines","Far Eastern University","Adamson University",

  // Vietnam
  "Vietnam National University Hanoi","Vietnam National University Ho Chi Minh City",
  "Hanoi University of Science and Technology (HUST)","Ho Chi Minh City University of Technology",
  "Foreign Trade University Vietnam","University of Economics Ho Chi Minh City",

  // Pakistan
  "University of Karachi","LUMS (Lahore University of Management Sciences)",
  "NUST (National University of Sciences and Technology)","IBA Karachi",
  "COMSATS University Islamabad","University of Engineering and Technology Lahore (UET)",
  "Quaid-i-Azam University Islamabad","University of the Punjab Lahore",
  "Aga Khan University","University of Peshawar","GIK Institute",
  "FAST-National University of Computer and Emerging Sciences","NED University Karachi",

  // Bangladesh
  "University of Dhaka","BUET (Bangladesh University of Engineering and Technology)",
  "BRAC University","North South University (NSU)","Independent University Bangladesh",
  "Chittagong University","Rajshahi University","Khulna University of Engineering and Technology",
  "American International University Bangladesh","East West University Bangladesh",

  // Sri Lanka
  "University of Colombo","University of Moratuwa","University of Peradeniya",
  "University of Sri Jayewardenepura","University of Kelaniya","Sabaragamuwa University",
  "South Eastern University of Sri Lanka",

  // Nepal
  "Tribhuvan University","Kathmandu University","Pokhara University",
  "Purbanchal University","Gandaki University","Lumbini Buddhist University",

  // ════════════════════════════════════════════════════════════
  //  M I D D L E  E A S T
  // ════════════════════════════════════════════════════════════

  "King Abdulaziz University (KAU)","King Fahd University of Petroleum and Minerals (KFUPM)",
  "King Abdullah University of Science and Technology (KAUST)","King Saud University (KSU)",
  "Prince Sultan University","King Faisal University","King Khalid University",
  "Qatar University","Carnegie Mellon University Qatar","Georgetown University Qatar",
  "Weill Cornell Medicine Qatar","Texas A&M at Qatar","Northwestern University in Qatar",
  "American University of Sharjah (AUS)","UAE University","Khalifa University Abu Dhabi",
  "Abu Dhabi University","University of Dubai","Zayed University",
  "American University in Dubai","University of Wollongong Dubai",
  "American University of Beirut (AUB)","Lebanese American University (LAU)",
  "American University in Cairo (AUC)","University of Jordan","Jordan University of Science and Technology",
  "Sultan Qaboos University Oman","University of Bahrain","Kuwait University",
  "University of Tehran","Sharif University of Technology","Amirkabir University of Technology",
  "Isfahan University of Technology","Tarbiat Modares University",
  "Istanbul University","Istanbul Technical University (ITU)","Middle East Technical University (METU)",
  "Bogazici University","Bilkent University","Sabanci University","Koç University",
  "Yıldız Technical University","Ankara University","Hacettepe University","Ege University",

  // ════════════════════════════════════════════════════════════
  //  A F R I C A
  // ════════════════════════════════════════════════════════════

  "University of Cape Town (UCT)","University of the Witwatersrand (Wits)",
  "Stellenbosch University","University of Pretoria","University of Johannesburg",
  "University of KwaZulu-Natal (UKZN)","Rhodes University","Nelson Mandela University",
  "North-West University","University of the Free State","University of the Western Cape",
  "University of Limpopo","UNISA (University of South Africa)",
  "University of Nairobi","Strathmore University Kenya","USIU-Africa Kenya",
  "Makerere University Uganda","University of Dar es Salaam","University of Ghana",
  "Kwame Nkrumah University of Science and Technology (KNUST)","Ashesi University Ghana",
  "University of Lagos","Obafemi Awolowo University","University of Ibadan",
  "University of Benin Nigeria","Ahmadu Bello University Zaria","University of Port Harcourt",
  "Covenant University Ota","Pan-Atlantic University Lagos","Lagos Business School",
  "Cairo University","Ain Shams University","Alexandria University","American University in Cairo",
  "University of Tunis","University of Carthage Tunisia","Mohammed V University Rabat",
  "University Hassan II Casablanca","Cadi Ayyad University Marrakech",
  "Addis Ababa University","University of Khartoum","University of Zambia",
  "University of Zimbabwe","University of Dar es Salaam","University of Rwanda",
  "University of Botswana","National University of Lesotho",

  // ════════════════════════════════════════════════════════════
  //  L A T I N  A M E R I C A
  // ════════════════════════════════════════════════════════════

  "University of São Paulo (USP)","Universidade Estadual de Campinas (UNICAMP)",
  "PUC-Rio (Pontifical Catholic University of Rio de Janeiro)",
  "Federal University of Rio de Janeiro (UFRJ)","UNESP","Federal University of Minas Gerais (UFMG)",
  "Federal University of Rio Grande do Sul (UFRGS)","University of Brasília (UnB)",
  "Pontifical Catholic University of Chile (PUC Chile)","University of Chile",
  "Federico Santa María Technical University","Universidad de los Andes Colombia",
  "Universidad Nacional de Colombia","Universidad del Rosario","Pontificia Universidad Javeriana",
  "Universidad Nacional Autónoma de México (UNAM)","Tecnológico de Monterrey (ITESM)",
  "Universidad Iberoamericana Mexico","IPN Mexico City","CINVESTAV",
  "Pontificia Universidad Católica del Perú","Universidad de Lima","Universidad Peruana Cayetano Heredia",
  "Universidad de Buenos Aires (UBA)","Universidad Nacional de Córdoba Argentina",
  "Instituto Tecnológico de Buenos Aires (ITBA)","Universidad Austral Argentina",
  "Universidad de la República Uruguay","Pontificia Universidad Católica de Ecuador",
  "Universidad Central de Venezuela","Universidad Simón Bolívar Venezuela",
  "Universidad de los Andes Venezuela",

  // ════════════════════════════════════════════════════════════
  //  O C E A N I A  ( O T H E R )
  // ════════════════════════════════════════════════════════════

  "University of Papua New Guinea","Fiji National University","University of the South Pacific",
  "National University of Samoa","University of Guam",

  // ════════════════════════════════════════════════════════════
  //  O T H E R
  // ════════════════════════════════════════════════════════════
  "Other (not listed)",
];
sort($universities);

$page_title='My Profile';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">

  <aside class="sidebar">
    <div class="sidebar-card">
      <?php if($role==='candidate'): ?>
        <a href="candidate_dashboard.php" class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
        <a href="browse_tasks.php"         class="sidebar-link"><span class="si">🔍</span>Browse Tasks</a>
        <a href="my_applications.php"      class="sidebar-link"><span class="si">📋</span>My Bids</a>
        <a href="saved_tasks.php"          class="sidebar-link"><span class="si">🔖</span>Saved Tasks</a>
        <a href="my_earnings.php"          class="sidebar-link"><span class="si">💰</span>Earnings</a>
        <a href="portfolio.php"            class="sidebar-link"><span class="si">🖼️</span>Portfolio</a>
        <a href="messages.php"             class="sidebar-link"><span class="si">💬</span>Messages</a>
        <a href="leaderboard.php"          class="sidebar-link"><span class="si">🏆</span>Leaderboard</a>
        <a href="notifications.php"        class="sidebar-link"><span class="si">🔔</span>Notifications</a>
      <?php elseif($role==='employer'): ?>
        <a href="employer_dashboard.php"    class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
        <a href="post_task.php"             class="sidebar-link"><span class="si">➕</span>Post Task</a>
        <a href="manage_tasks.php"          class="sidebar-link"><span class="si">📋</span>My Tasks</a>
        <a href="task_invite.php"           class="sidebar-link"><span class="si">📨</span>Invite Students</a>
        <a href="task_analytics.php"        class="sidebar-link"><span class="si">📊</span>Analytics</a>
        <a href="messages.php"              class="sidebar-link"><span class="si">💬</span>Messages</a>
        <a href="payment_history.php"       class="sidebar-link"><span class="si">💳</span>Payments</a>
        <a href="notifications.php"         class="sidebar-link"><span class="si">🔔</span>Notifications</a>
      <?php else: /* admin */ ?>
        <a href="admin_dashboard.php"       class="sidebar-link"><span class="si">📊</span>Dashboard</a>
      <?php endif; ?>
      <a href="profile.php"              class="sidebar-link active"><span class="si">👤</span>Profile</a>
      <?php if($role !== 'admin'): ?>
      <a href="notification_settings.php" class="sidebar-link"><span class="si">⚙️</span>Settings</a>
      <?php endif; ?>
      <a href="logout.php"               class="sidebar-link" style="color:var(--rose);margin-top:8px"><span class="si">🚪</span>Logout</a>
    </div>
  </aside>

  <main class="main-content">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
      <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:800">👤 My Profile</h1>
      <?php if($role==='candidate' && count($completed_tasks)>0): ?>
      <button onclick="document.getElementById('myGigModal').classList.add('show')" class="btn btn-brand">
        🎯 My Gig
      </button>
      <?php endif; ?>
    </div>

    <?php if($error):  ?><div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if($success):?><div class="alert alert-ok">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

      <!-- LEFT: Avatar + Stats + Reviews -->
      <div>
        <!-- Avatar Upload -->
        <div class="glass-dark" style="padding:28px;border-radius:var(--r);margin-bottom:20px">
          <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:20px">📸 Profile Picture</h3>
          <div style="display:flex;align-items:center;gap:20px;margin-bottom:20px">
            <div style="position:relative">
              <?php if($user['avatar'] && file_exists($user['avatar'])): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar"
                  style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--teal)">
              <?php else: ?>
                <div class="avatar avatar-xl"><?= strtoupper(substr($user['name'],0,1)) ?></div>
              <?php endif; ?>
              <label for="avatarInput" style="position:absolute;bottom:0;right:0;background:var(--teal);border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:13px;border:2px solid var(--ink2)" title="Change photo">✏️</label>
            </div>
            <div>
              <div style="font-weight:600;font-size:1rem"><?= htmlspecialchars($user['name']) ?></div>
              <div style="color:var(--t3);font-size:13px"><?= htmlspecialchars($user['university']??$user['company_name']??'') ?></div>
              <div style="color:var(--t3);font-size:12px;margin-top:4px">Max 3MB · JPG, PNG, WEBP</div>
            </div>
          </div>
          <form method="POST" enctype="multipart/form-data">
            <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this)">
            <div id="avatar-preview-wrap" style="display:none;margin-bottom:14px;text-align:center">
              <img id="avatar-preview" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--teal)">
              <div style="font-size:12px;color:var(--t3);margin-top:6px" id="avatar-filename"></div>
            </div>
            <label for="avatarInput" class="btn btn-glass btn-full" style="cursor:pointer;justify-content:center">📷 Choose Photo</label>
            <button type="submit" id="uploadBtn" class="btn btn-brand btn-full" style="margin-top:10px;display:none">⬆️ Upload Photo</button>
          </form>
        </div>

        <?php if($role==='candidate'): ?>
        <!-- Stats Card -->
        <div class="glass-dark" style="padding:24px;border-radius:var(--r);margin-bottom:20px">
          <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:16px">📊 Your Stats</h3>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:14px;text-align:center">
              <div style="font-size:1.5rem;font-weight:800;color:var(--emerald)"><?= $completed_count ?></div>
              <div style="font-size:11.5px;color:var(--t3);margin-top:3px">Tasks Completed</div>
            </div>
            <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:14px;text-align:center">
              <div style="font-size:1.5rem;font-weight:800;color:var(--amber)"><?= number_format($avg_r,1) ?>/5</div>
              <div style="font-size:11.5px;color:var(--t3);margin-top:3px">Avg Rating</div>
            </div>
          </div>
          <?php if($user['github']||$user['linkedin']||$user['portfolio']): ?>
          <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
            <?php if($user['github']):?><a href="<?=htmlspecialchars($user['github'])?>" target="_blank" class="btn btn-glass btn-sm">🐙 GitHub</a><?php endif;?>
            <?php if($user['linkedin']):?><a href="<?=htmlspecialchars($user['linkedin'])?>" target="_blank" class="btn btn-glass btn-sm">💼 LinkedIn</a><?php endif;?>
            <?php if($user['portfolio']):?><a href="<?=htmlspecialchars($user['portfolio'])?>" target="_blank" class="btn btn-glass btn-sm">🌐 Portfolio</a><?php endif;?>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Reviews -->
        <div class="glass-dark" style="padding:24px;border-radius:var(--r)">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
            <h3 style="font-family:var(--fh);font-weight:700">⭐ Reviews Received</h3>
            <?php for($i=1;$i<=5;$i++): ?><span class="star <?= $i<=$avg_r?'on':'off' ?>">★</span><?php endfor; ?>
            <span style="font-size:13px;color:var(--t3)"><?= number_format($avg_r,1) ?>/5</span>
          </div>
          <?php if(mysqli_num_rows($reviews)===0): ?>
            <p style="color:var(--t3);font-size:14px">No reviews yet. Complete tasks to earn reviews!</p>
          <?php else: ?>
            <?php while($rev=$reviews->fetch_assoc()): ?>
            <div style="border-bottom:1px solid var(--border);padding:12px 0">
              <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                <span style="font-weight:600;font-size:14px"><?= htmlspecialchars($rev['reviewer_name']) ?></span>
                <div><?php for($i=1;$i<=5;$i++): ?><span class="star <?= $i<=$rev['rating']?'on':'off' ?>">★</span><?php endfor; ?></div>
              </div>
              <div style="color:var(--t3);font-size:11.5px;margin-bottom:5px">on: <?= htmlspecialchars($rev['task_title']) ?></div>
              <?php if($rev['comment']): ?><p style="color:var(--t2);font-size:13.5px"><?= htmlspecialchars($rev['comment']) ?></p><?php endif; ?>
            </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT: Edit Profile (no password here) -->
      <div class="glass-dark" style="padding:28px;border-radius:var(--r)">
        <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:20px">✏️ Edit Profile</h3>
        <form method="POST">
          <input type="hidden" name="update_profile" value="1">

          <div class="fg">
            <label class="flabel">Full Name</label>
            <input type="text" name="name" class="finput" value="<?= htmlspecialchars($user['name']) ?>" required>
          </div>
          <div class="fg">
            <label class="flabel">Email</label>
            <input type="email" name="email" class="finput" value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>
          <div class="fg">
            <label class="flabel">Bio</label>
            <textarea name="bio" class="finput" rows="3" placeholder="Tell us about yourself…"><?= htmlspecialchars($user['bio']??'') ?></textarea>
          </div>
          <div class="fg">
            <label class="flabel">Location</label>
            <input type="text" name="location" class="finput" value="<?= htmlspecialchars($user['location']??'') ?>" placeholder="e.g. Mumbai, India">
          </div>

          <?php if($role==='candidate'): ?>
          <div class="fg">
            <label class="flabel">University</label>
            <div style="position:relative" id="uni-wrap">
              <input type="text" id="uni-search" class="finput" placeholder="Search university…"
                value="<?= htmlspecialchars($user['university']??'') ?>" autocomplete="off">
              <input type="hidden" name="university" id="uni-val" value="<?= htmlspecialchars($user['university']??'') ?>">
              <div id="uni-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:var(--ink2);border:1px solid var(--border2);border-radius:10px;max-height:200px;overflow-y:auto;z-index:500;box-shadow:0 12px 30px rgba(0,0,0,0.4)">
                <?php foreach($universities as $u): ?>
                  <div class="uni-opt" data-val="<?= htmlspecialchars($u) ?>"
                    style="padding:9px 14px;cursor:pointer;font-size:13px;color:var(--t2);border-bottom:1px solid var(--border);transition:var(--tr)">
                    <?= htmlspecialchars($u) ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="fg">
            <label class="flabel">Skills <span style="color:var(--t3);font-weight:400">(comma-separated)</span></label>
            <input type="text" name="skills" class="finput" value="<?= htmlspecialchars($user['skills']??'') ?>" placeholder="React, Figma, Python, MySQL…">
          </div>
          <div class="fg">
            <label class="flabel">GitHub</label>
            <div class="fwrap"><span class="ficon">🐙</span>
              <input type="url" name="github" class="finput" value="<?= htmlspecialchars($user['github']??'') ?>" placeholder="https://github.com/username">
            </div>
          </div>
          <div class="fg">
            <label class="flabel">LinkedIn</label>
            <div class="fwrap"><span class="ficon">💼</span>
              <input type="url" name="linkedin" class="finput" value="<?= htmlspecialchars($user['linkedin']??'') ?>">
            </div>
          </div>
          <div class="fg">
            <label class="flabel">Portfolio URL</label>
            <div class="fwrap"><span class="ficon">🌐</span>
              <input type="url" name="portfolio" class="finput" value="<?= htmlspecialchars($user['portfolio']??'') ?>">
            </div>
          </div>
          <?php else: ?>
          <div class="fg">
            <label class="flabel">Company Name</label>
            <input type="text" name="company_name" class="finput" value="<?= htmlspecialchars($user['company_name']??'') ?>">
          </div>
          <?php endif; ?>

          <button type="submit" class="btn btn-brand btn-full">💾 Save Changes</button>
        </form>

        <!-- Settings link for password -->
        <div style="margin-top:18px;padding:16px;background:rgba(59,130,246,0.07);border:1px solid rgba(59,130,246,0.15);border-radius:var(--rs)">
          <div style="font-size:13px;color:var(--t2);margin-bottom:10px">🔒 Need to change your password?</div>
          <a href="notification_settings.php#password" class="btn btn-glass btn-full" style="justify-content:center">Go to Settings →</a>
        </div>
      </div>
    </div>
  </main>
</div>
</div>

<!-- My Gig Modal -->
<?php if($role==='candidate'): ?>
<div class="overlay" id="myGigModal" onclick="if(event.target===this)this.classList.remove('show')">
  <div class="mbox mbox-wide">
    <button class="mclose" onclick="document.getElementById('myGigModal').classList.remove('show')">✕</button>
    <h2 style="font-family:var(--fh);font-weight:800;margin-bottom:6px">🎯 My Gig</h2>
    <p style="color:var(--t3);font-size:13px;margin-bottom:22px">All tasks you have successfully completed</p>
    <?php if(count($completed_tasks)===0): ?>
      <div style="text-align:center;padding:40px;color:var(--t3)">
        <div style="font-size:3rem;margin-bottom:12px">📋</div>
        <p>No completed tasks yet. Start bidding to build your gig portfolio!</p>
        <a href="browse_tasks.php" class="btn btn-brand" style="margin-top:16px">Browse Tasks →</a>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:10px;max-height:420px;overflow-y:auto;padding-right:4px">
        <?php foreach($completed_tasks as $i=>$gig): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--ink3);border:1px solid var(--border);border-radius:var(--rs);gap:12px">
          <div style="display:flex;align-items:center;gap:12px">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--grad-brand);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0"><?= $i+1 ?></div>
            <div>
              <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($gig['title']) ?></div>
              <span class="tag tag-g" style="font-size:11px;margin-top:4px"><?= htmlspecialchars($gig['category']) ?></span>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
            <span style="color:var(--emerald);font-size:12px;font-weight:600">✅ Completed</span>
            <a href="certificate.php?app_id=<?= $gig['app_id'] ?>" target="_blank" class="btn btn-glass btn-sm">🏆 Certificate</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:16px;text-align:center;color:var(--t3);font-size:13px">
        <?= count($completed_tasks) ?> task<?= count($completed_tasks)!==1?'s':'' ?> completed
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<style>
.uni-opt:hover { background:rgba(0,201,167,0.15)!important; color:var(--t1)!important; }
#uni-dropdown::-webkit-scrollbar{width:4px} #uni-dropdown::-webkit-scrollbar-thumb{background:var(--teal);border-radius:2px}
</style>

<script>
function previewAvatar(inp){
  if(!inp.files[0]) return;
  const r=new FileReader();
  r.onload=e=>{
    document.getElementById('avatar-preview').src=e.target.result;
    document.getElementById('avatar-preview-wrap').style.display='block';
    document.getElementById('avatar-filename').textContent=inp.files[0].name+' ('+Math.round(inp.files[0].size/1024)+'KB)';
    document.getElementById('uploadBtn').style.display='block';
  };
  r.readAsDataURL(inp.files[0]);
}

(function(){
  const search=document.getElementById('uni-search'), hidden=document.getElementById('uni-val'), drop=document.getElementById('uni-dropdown');
  if(!search) return;
  const opts=document.querySelectorAll('#uni-dropdown .uni-opt');
  let hi=-1;
  function show(q){ const lq=q.toLowerCase(); let v=0; opts.forEach(o=>{ const m=o.dataset.val.toLowerCase().includes(lq); o.style.display=m?'':'none'; if(m)v++; }); drop.style.display=q.length>0&&v>0?'':'none'; hi=-1; }
  search.addEventListener('input',()=>{ hidden.value=search.value; show(search.value); });
  search.addEventListener('focus',()=>{ if(search.value) show(search.value); });
  opts.forEach(o=>{ o.addEventListener('mousedown',e=>{ e.preventDefault(); search.value=o.dataset.val; hidden.value=o.dataset.val; drop.style.display='none'; }); });
  search.addEventListener('keydown',e=>{ const vis=[...opts].filter(o=>o.style.display!=='none'); if(e.key==='ArrowDown'){e.preventDefault();hi=Math.min(hi+1,vis.length-1);} else if(e.key==='ArrowUp'){e.preventDefault();hi=Math.max(hi-1,0);} else if(e.key==='Enter'&&hi>=0){e.preventDefault();vis[hi].dispatchEvent(new MouseEvent('mousedown'));} else if(e.key==='Escape')drop.style.display='none'; vis.forEach((o,i)=>o.style.background=i===hi?'rgba(59,130,246,0.2)':''); if(hi>=0)vis[hi].scrollIntoView({block:'nearest'}); });
  document.addEventListener('click',e=>{ if(!document.getElementById('uni-wrap')?.contains(e.target))drop.style.display='none'; });
})();
</script>

<?php include 'includes/footer.php'; ?>
