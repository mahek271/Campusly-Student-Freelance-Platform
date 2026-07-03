<?php
session_start();
include 'db.php';
if(isset($_SESSION['user_id'])){ header("Location: index.php"); exit(); }

$error = '';
$default_role = $_GET['role'] ?? 'candidate';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $name        = trim($_POST['name']        ?? '');
  $email       = trim($_POST['email']       ?? '');
  $password    =      $_POST['password']    ?? '';
  $confirm     =      $_POST['confirm']     ?? '';
  $role        =      $_POST['role']        ?? 'candidate';
  $university  = trim($_POST['university']  ?? '');
  $company_name= trim($_POST['company_name']?? '');
  $skills_arr  =      $_POST['skills']      ?? [];
  $skills      = is_array($skills_arr) ? implode(',', array_map('trim', $skills_arr)) : trim($skills_arr);
  $github      = trim($_POST['github']      ?? '');
  $linkedin    = trim($_POST['linkedin']    ?? '');

  if(!$name||!$email||!$password||!$confirm){ $error='All required fields must be filled.'; }
  elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){ $error='Enter a valid email address.'; }
  elseif(strlen($password)<8){ $error='Password must be at least 8 characters.'; }
  elseif(!preg_match('/[A-Z]/',$password)||!preg_match('/[0-9]/',$password)){ $error='Password needs at least 1 uppercase letter and 1 number.'; }
  elseif($password!==$confirm){ $error='Passwords do not match.'; }
  elseif(!in_array($role,['candidate','employer'])){ $error='Invalid role.'; }
  else {
    $stmt=$conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s",$email); $stmt->execute();
    if($stmt->get_result()->num_rows>0){ $error='An account with this email already exists.'; }
    else {
      $hashed=password_hash($password,PASSWORD_DEFAULT);
      $stmt2=$conn->prepare("INSERT INTO users (name,email,password,role,university,company_name,skills,github,linkedin,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
      $stmt2->bind_param("sssssssss",$name,$email,$hashed,$role,$university,$company_name,$skills,$github,$linkedin);
      if($stmt2->execute()){
        $uid=$conn->insert_id;
        $_SESSION['user_id']=$uid; $_SESSION['user_name']=$name; $_SESSION['user_role']=$role;
        $_SESSION['flash_success']="Welcome to Campusly, $name! 🎉";
        header("Location: ".($role==='employer'?'employer_dashboard.php':'candidate_dashboard.php')); exit();
      } else { $error='Registration failed. Please try again.'; }
    }
  }
}

// ── World Universities list ──────────────────────────────────────────
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

// Common skills for selection
$skill_options = [
  "Development" => ["JavaScript","TypeScript","React","Vue.js","Angular","Node.js","Python","Django","Flask","PHP","Laravel","Java","Spring Boot","C++","C#","Go","Ruby on Rails","Swift","Kotlin","Flutter","Android Dev","iOS Dev","React Native","Firebase","MongoDB","MySQL","PostgreSQL","REST API","GraphQL","Docker","AWS","Git"],
  "Design" => ["Figma","Adobe XD","Photoshop","Illustrator","After Effects","Premiere Pro","Canva","UI Design","UX Design","Logo Design","Branding","Motion Graphics","3D Design","Blender","Sketch"],
  "Content & Marketing" => ["Content Writing","Copywriting","SEO","Social Media","Email Marketing","WordPress","Blog Writing","Technical Writing","Video Editing","YouTube","Instagram Marketing","LinkedIn Marketing","Google Ads","Facebook Ads"],
  "Data & Research" => ["Python","R","Machine Learning","Deep Learning","TensorFlow","Pandas","NumPy","SQL","Tableau","Power BI","Excel","Data Analysis","Market Research","Statistics","Data Visualization"],
  "Other" => ["Project Management","Business Analysis","Accounting","Tally","AutoCAD","SolidWorks","Arduino","Raspberry Pi","Cyber Security","Network Administration","Linux","Digital Marketing","Public Speaking","Translation"],
];

$page_title = 'Create Account';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div class="auth-wrap" style="padding-top:100px;align-items:flex-start">
    <div class="auth-card" style="max-width:520px">
      <div class="auth-logo">
        <div class="brand-mark" style="margin-right:8px">🎯</div>
        <span class="brand-name">Campusly</span>
      </div>
      <h1 class="auth-title">Create your account</h1>
      <p class="auth-sub">Join thousands earning on Campusly</p>

      <?php if($error): ?>
        <div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" id="regForm">

        <!-- ROLE TOGGLE -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px">
          <?php foreach(['candidate'=>['🎓','Student','Find & bid on tasks'],'employer'=>['🏢','Employer','Post tasks & hire']] as $r=>[$icon,$label,$sub]): ?>
          <label style="cursor:pointer">
            <input type="radio" name="role" value="<?= $r ?>" <?= ($default_role===$r||($_POST['role']??'')===$r)?'checked':'' ?> style="display:none" class="role-radio">
            <div class="role-opt" data-role="<?= $r ?>" style="padding:14px;border-radius:12px;border:2px solid var(--border2);text-align:center;transition:var(--tr)">
              <div style="font-size:24px"><?= $icon ?></div>
              <div style="font-weight:700;font-size:14px;margin-top:4px"><?= $label ?></div>
              <div style="color:var(--t3);font-size:11px"><?= $sub ?></div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>

        <div class="fg">
          <label class="flabel">Full Name <span style="color:var(--rose)">*</span></label>
          <div class="fwrap"><span class="ficon">👤</span>
            <input type="text" name="name" class="finput" placeholder="Your full name" value="<?= htmlspecialchars($_POST['name']??'') ?>" required>
          </div>
        </div>

        <div class="fg">
          <label class="flabel">Email Address <span style="color:var(--rose)">*</span></label>
          <div class="fwrap"><span class="ficon">✉️</span>
            <input type="email" name="email" class="finput" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
          </div>
        </div>

        <!-- Student fields -->
        <div id="student-fields" style="<?= (($_POST['role']??$default_role)!=='candidate')?'display:none':'' ?>">
          <div class="fg">
            <label class="flabel">University / College <span style="color:var(--rose)">*</span></label>
            <!-- Searchable dropdown -->
            <div style="position:relative" id="uni-wrap">
              <div class="fwrap">
                <span class="ficon">🏫</span>
                <input type="text" id="uni-search" class="finput" placeholder="Search your university…"
                       value="<?= htmlspecialchars($_POST['university']??'') ?>" autocomplete="off">
              </div>
              <input type="hidden" name="university" id="uni-val" value="<?= htmlspecialchars($_POST['university']??'') ?>">
              <div id="uni-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:var(--ink2);border:1px solid var(--border2);border-radius:10px;max-height:220px;overflow-y:auto;z-index:500;box-shadow:0 12px 30px rgba(0,0,0,0.4)">
                <?php foreach($universities as $u): ?>
                  <div class="uni-opt" data-val="<?= htmlspecialchars($u) ?>"
                       style="padding:10px 16px;cursor:pointer;font-size:13.5px;color:var(--t2);transition:var(--tr);border-bottom:1px solid var(--border)">
                    <?= htmlspecialchars($u) ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Skills Multi-select -->
          <div class="fg">
            <label class="flabel">Your Skills (select all that apply)</label>
            <div id="skills-panel" style="background:rgba(255,255,255,0.03);border:1px solid var(--border2);border-radius:var(--rs);padding:16px;max-height:280px;overflow-y:auto">
              <?php foreach($skill_options as $cat=>$skills_list): ?>
                <div style="margin-bottom:14px">
                  <div style="font-size:11px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px"><?= $cat ?></div>
                  <div style="display:flex;flex-wrap:wrap;gap:6px">
                    <?php foreach($skills_list as $sk):
                      $checked = in_array($sk, $_POST['skills']??[]) ? 'checked' : '';
                    ?>
                    <label style="cursor:pointer">
                      <input type="checkbox" name="skills[]" value="<?= htmlspecialchars($sk) ?>" <?= $checked ?> style="display:none" class="skill-cb">
                      <span class="skill-tag" style="display:inline-block;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid var(--border2);color:var(--t3);transition:var(--tr);cursor:pointer">
                        <?= htmlspecialchars($sk) ?>
                      </span>
                    </label>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="fhelp" style="margin-top:6px">Selected: <span id="skill-count">0</span> skills</div>
          </div>

          <div class="fg">
            <label class="flabel">GitHub Profile (optional)</label>
            <div class="fwrap"><span class="ficon">🐙</span>
              <input type="url" name="github" class="finput" placeholder="https://github.com/username" value="<?= htmlspecialchars($_POST['github']??'') ?>">
            </div>
          </div>
          <div class="fg">
            <label class="flabel">LinkedIn (optional)</label>
            <div class="fwrap"><span class="ficon">💼</span>
              <input type="url" name="linkedin" class="finput" placeholder="https://linkedin.com/in/username" value="<?= htmlspecialchars($_POST['linkedin']??'') ?>">
            </div>
          </div>
        </div>

        <!-- Employer fields -->
        <div id="employer-fields" style="<?= (($_POST['role']??$default_role)!=='employer')?'display:none':'' ?>">
          <div class="fg">
            <label class="flabel">Company / Organisation Name <span style="color:var(--rose)">*</span></label>
            <div class="fwrap"><span class="ficon">🏢</span>
              <input type="text" name="company_name" class="finput" placeholder="e.g. Acme Pvt. Ltd." value="<?= htmlspecialchars($_POST['company_name']??'') ?>">
            </div>
          </div>
        </div>

        <!-- Password -->
        <div class="fg">
          <label class="flabel">Password <span style="color:var(--rose)">*</span></label>
          <div style="position:relative">
            <input type="password" name="password" id="pwd" class="finput" placeholder="Min 8 chars, 1 uppercase, 1 number" required>
            <span onclick="togglePwd('pwd','eye1')" id="eye1" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--t3);font-size:18px">👁</span>
          </div>
          <!-- Strength meter -->
          <div style="margin-top:8px">
            <div class="progress" style="height:4px"><div id="pwd-strength-bar" class="progress-bar" style="width:0%;background:var(--rose)"></div></div>
            <div id="pwd-strength-label" style="font-size:11px;color:var(--t3);margin-top:4px"></div>
          </div>
        </div>
        <div class="fg">
          <label class="flabel">Confirm Password <span style="color:var(--rose)">*</span></label>
          <div style="position:relative">
            <input type="password" name="confirm" id="cpwd" class="finput" placeholder="Repeat password" required>
            <span onclick="togglePwd('cpwd','eye2')" id="eye2" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--t3);font-size:18px">👁</span>
          </div>
          <div id="pwd-match" style="font-size:12px;margin-top:5px"></div>
        </div>

        <div style="margin-bottom:18px;display:flex;align-items:flex-start;gap:10px">
          <input type="checkbox" name="terms" id="terms" required style="margin-top:3px;accent-color:var(--teal)">
          <label for="terms" style="font-size:13px;color:var(--t2);cursor:pointer">
            I agree to Campusly's <a href="#" style="color:var(--violet2)">Terms of Service</a> and <a href="#" style="color:var(--violet2)">Privacy Policy</a>
          </label>
        </div>

        <button type="submit" class="btn btn-brand btn-full btn-lg">Create Account 🚀</button>
      </form>

      <p style="text-align:center;margin-top:20px;color:var(--t3);font-size:13.5px">
        Already have an account? <a href="login.php" style="color:var(--violet2);font-weight:600">Sign in</a>
      </p>
    </div>
  </div>
</div>

<style>
.skill-tag.active { background:rgba(0,201,167,0.2)!important; color:var(--teal2)!important; border-color:rgba(0,201,167,0.4)!important; }
.uni-opt:hover,.uni-opt.highlighted { background:rgba(0,201,167,0.15); color:var(--t1); }
#skills-panel::-webkit-scrollbar{width:4px} #skills-panel::-webkit-scrollbar-thumb{background:var(--teal);border-radius:2px}
#uni-dropdown::-webkit-scrollbar{width:4px} #uni-dropdown::-webkit-scrollbar-thumb{background:var(--teal);border-radius:2px}
</style>

<script>
// ── Role toggle ──────────────────────────────────────────────
document.querySelectorAll('.role-radio').forEach(r=>{
  r.addEventListener('change',()=>{
    const role=r.value;
    document.querySelectorAll('.role-opt').forEach(o=>{
      const on=o.dataset.role===role;
      o.style.borderColor=on?'var(--teal)':'var(--border2)';
      o.style.background=on?'rgba(0,201,167,0.12)':'transparent';
    });
    document.getElementById('student-fields').style.display=role==='candidate'?'':'none';
    document.getElementById('employer-fields').style.display=role==='employer'?'':'none';
  });
  // Init
  if(r.checked) r.dispatchEvent(new Event('change'));
});

// ── University searchable dropdown ────────────────────────────
(function(){
  const search = document.getElementById('uni-search');
  const hidden = document.getElementById('uni-val');
  const drop   = document.getElementById('uni-dropdown');
  const opts   = document.querySelectorAll('.uni-opt');
  let hiIdx    = -1;

  function show(q){
    const lq = q.toLowerCase();
    let visible=0;
    opts.forEach((o,i)=>{
      const match = o.dataset.val.toLowerCase().includes(lq);
      o.style.display = match ? '' : 'none';
      if(match) visible++;
    });
    drop.style.display = q.length>0 && visible>0 ? '' : 'none';
    hiIdx=-1;
  }

  search.addEventListener('input',()=>{ hidden.value=search.value; show(search.value); });
  search.addEventListener('focus',()=>{ if(search.value.length>0) show(search.value); });

  opts.forEach(o=>{
    o.addEventListener('mousedown',e=>{
      e.preventDefault();
      search.value = o.dataset.val;
      hidden.value  = o.dataset.val;
      drop.style.display='none';
    });
  });

  // Keyboard nav
  search.addEventListener('keydown',e=>{
    const vis=[...opts].filter(o=>o.style.display!=='none');
    if(e.key==='ArrowDown'){ e.preventDefault(); hiIdx=Math.min(hiIdx+1,vis.length-1); }
    else if(e.key==='ArrowUp'){ e.preventDefault(); hiIdx=Math.max(hiIdx-1,0); }
    else if(e.key==='Enter' && hiIdx>=0){ e.preventDefault(); vis[hiIdx].dispatchEvent(new MouseEvent('mousedown')); }
    else if(e.key==='Escape'){ drop.style.display='none'; }
    vis.forEach((o,i)=>o.classList.toggle('highlighted',i===hiIdx));
    if(hiIdx>=0) vis[hiIdx].scrollIntoView({block:'nearest'});
  });

  document.addEventListener('click',e=>{ if(!document.getElementById('uni-wrap').contains(e.target)) drop.style.display='none'; });
})();

// ── Skill chip toggle ────────────────────────────────────────
(function(){
  const counter = document.getElementById('skill-count');
  function upd(){ counter.textContent = document.querySelectorAll('.skill-cb:checked').length; }

  document.querySelectorAll('.skill-cb').forEach(cb => {
    const tag = cb.nextElementSibling;
    // Set initial visual state (for POST-back)
    if(cb.checked) tag.classList.add('active');

    // The <label> wrapping both checkbox+tag already handles toggling the checkbox
    // on click. We just need to react to that change and update the visual + counter.
    cb.addEventListener('change', () => {
      tag.classList.toggle('active', cb.checked);
      upd();
    });
  });

  upd();
})();

// ── Password strength ────────────────────────────────────────
(function(){
  const pwd=document.getElementById('pwd');
  const bar=document.getElementById('pwd-strength-bar');
  const lbl=document.getElementById('pwd-strength-label');
  const matchEl=document.getElementById('pwd-match');
  const cpwd=document.getElementById('cpwd');

  function strength(p){
    let s=0;
    if(p.length>=8)s++; if(p.length>=12)s++;
    if(/[A-Z]/.test(p))s++; if(/[0-9]/.test(p))s++; if(/[^A-Za-z0-9]/.test(p))s++;
    return s;
  }
  const colors=['var(--rose)','var(--rose)','var(--amber)','var(--amber)','var(--emerald)','var(--emerald)'];
  const labels=['','Weak','Weak','Fair','Good','Strong'];

  pwd.addEventListener('input',()=>{
    const s=strength(pwd.value);
    bar.style.width=(s/5*100)+'%';
    bar.style.background=colors[s]||'var(--emerald)';
    lbl.textContent=pwd.value.length?labels[s]+' password':'';
    lbl.style.color=colors[s];
    checkMatch();
  });

  function checkMatch(){
    if(!cpwd.value) return;
    const ok=pwd.value===cpwd.value;
    matchEl.textContent=ok?'✅ Passwords match':'❌ Passwords do not match';
    matchEl.style.color=ok?'var(--emerald)':'var(--rose)';
  }
  cpwd.addEventListener('input',checkMatch);
})();

// ── Show/hide password ───────────────────────────────────────
function togglePwd(id,eyeId){
  const el=document.getElementById(id);
  el.type=el.type==='password'?'text':'password';
}
</script>

<?php include 'includes/footer.php'; ?>
