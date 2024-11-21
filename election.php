<?php
session_start();
// Start output buffering
ob_start();


// Database connection (update as per your setup)
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'election_db';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Create necessary tables if not already present
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    gender VARCHAR(50),
    department VARCHAR(50),
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
     
)");

$conn->query("CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id INT NOT NULL UNIQUE,
    vice_president VARCHAR(255),
    joint_secretary VARCHAR(255),
    FOREIGN KEY (voter_id) REFERENCES users(id)
)");

define('PRINCIPAL_USERNAME', 'principal');
define('PRINCIPAL_PASSWORD', 'securepassword123');


// Messages
$success = $error = '';
$results = [];

// Handle Faculty Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['faculty_login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === PRINCIPAL_USERNAME && $password === PRINCIPAL_PASSWORD) {
        $_SESSION['is_principal'] = true;
        echo "<script>alert('Principal logged in! Redirecting to results...');</script>";
        header("Location: ?page=results");
        exit();
    } else {
        $error = "Invalid principal credentials.";
    }
}



// Fetch Election Results
if (isset($_SESSION['is_principal']) && $_SESSION['is_principal'] === true) {
    $query = "SELECT vice_president, COUNT(vice_president) as votes FROM votes GROUP BY vice_president";
    $vp_results = $conn->query($query);

    while ($row = $vp_results->fetch_assoc()) {
        $results['Vice President'][] = $row;
    }

    $query = "SELECT joint_secretary, COUNT(joint_secretary) as votes FROM votes GROUP BY joint_secretary";
    $js_results = $conn->query($query);

    while ($row = $js_results->fetch_assoc()) {
        $results['Joint Secretary'][] = $row;
    }
}


// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?page=login");
    exit();
}

// Handle User Signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = $_POST['name'];
    $gender = $conn->real_escape_string($_POST['gender']);
    $department = $conn->real_escape_string($_POST['department']);
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, gender, department, email, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $gender, $department, $email, $password);

    if ($stmt->execute()) {
        $success = "Sign-up successful! You can now log in.";
        header("Location: ?page=login");
        exit();
    } else {
        $error = "Sign-up failed: " . $stmt->error;
    }
}

// Handle User Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: ?page=election");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}

// Handle Voting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $user_id = $_SESSION['user_id'];
    $vice_president = $_POST['vice_president'];
    $joint_secretary = $_POST['joint_secretary'];

    // Check if the user has already voted
    $stmt = $conn->prepare("SELECT id FROM votes WHERE voter_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // User has already voted, so update their vote
        $stmt = $conn->prepare("UPDATE votes SET vice_president = ?, joint_secretary = ? WHERE voter_id = ?");
        $stmt->bind_param("ssi", $vice_president, $joint_secretary, $user_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO votes (voter_id, vice_president, joint_secretary) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $vice_president, $joint_secretary);
        $stmt->execute();
    }

    // Redirect to a confirmation page showing the selected candidates
    header("Location: ?page=voted&vp=$vice_president&js=$joint_secretary");
    exit();
}

// Define candidates
$vice_presidents = [
    'ISMANKHAN Y M (CSE)-2020', 'ARIVAZHAGAN E (MECH)-2020', 'PRAGADESHWARAN R (EEE)-2020', 
    'DINESH R (EEE)-2020', 'MOHAMED SALMAN KHAN S (EEE)-2020', 'VEERAMANI R (CIVIL)-2020'
];
$joint_secretaries = [
    'RAGUL A (MECH)-2021', 'RANGARAJAN B (ECE)-2021', 'ARUN KUMAR R (EEE)-2021', 'AKARAM MEERAN (EEE)-2021'
];

// Default page
$page = isset($_GET['page']) ? $_GET['page'] : 'login'; // Default to 'login' if 'page' is not set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni Election</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Jost:ital,wght@0,100..900;1,100..900&family=Kalam:wght@300;400;700&family=Public+Sans:ital,wght@0,100..900;1,100..900&family=Red+Hat+Display:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
    <style>


<style>
        /* Global Styles */
        body {
            font-family: 'Jost', sans-serif;
            background-color: #feb47b;
            margin: 0;
            padding: 0;
         
        }
        .main-heading {
            font-family: 'Archivo Black', sans-serif;
            font-size: 2.5rem;
            margin-top: 20px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .main-heading {
                font-size: 1.8rem;
            }
            #login-body, .signup-body {
                flex-direction: column !important;
                gap: 20px;
            }
        }

        /* Voting Cards */
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .card-content {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card-content:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .card-content img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Custom Media Query for Small Devices */
        @media (max-width: 576px) {
            .card-content img {
                height: 100px;
            }
        }

        /* Footer Adjustments */
        footer {
            background-color: #343a40;
            color: #fff;
            padding: 10px;
            text-align: center;
        }
       * {
            font-family: "Public Sans", sans-serif;
            font-style: normal;
            
          
        }

        
        .main-heading{
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            font-size: 50px;
            color:white;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            background: linear-gradient(to right, #ff7e5f, #feb47b);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 3px 3px 5px rgba(0, 0, 0, 0.1);
  
            


        }

#login-body{
    display:flex;
    margin-top: 20px;
    
     
}
.login-heading{
    text-align: center;
}

#studentcard{
    border:2px dotted #34a1eb;
    border-radius:10px;
}
#principalcard{
    border:2px dotted #34a1eb;
    border-radius:10px;
}

.student{

            width: 400px;
            margin: auto;
            background-color: #fff;
            padding:20px 20px 40px 20px; 
            border:solid 2px black;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-right:40px;
}
#container {
            width: 600px;
            margin: auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            border:solid 2px #34a1eb;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

.principal{
            width: 400px;
            margin: auto;
            background-color: #fff;
            padding: 20px 20px 50px 20px;
            border:solid 2px black;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);

}

       

        h2 {
            text-align: center;
            color: #34a1eb;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            border: none;
        }

        

        .principal [type="submit"]{
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: orangered;
            color: black;
            cursor: pointer;
            border: none;  
            transition: background-color 0.3s, transform 0.3s; 
        }
        .student [type="submit"]{
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: orangered;
            color: black;
            cursor: pointer;
            border: none;  
            transition: background-color 0.2s, transform 0.2s; 
        }



        .form-group input[type="submit"]:hover {
            background-color: orangered;
             
          
        }


        
        .form-group a {
            text-align: center;
            display: block;
            color: #333;
            text-decoration: none;
            margin-top: 10px;
        }

        
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 15px;
        }


        .card-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
}

.card {
    display: flex;
    background-color: #fff;
    flex-direction: column;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 8px;
    width: 150px;
    padding: 10px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}
.card input {
    display: none;
}

.card-content {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.card img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 10px;
}

.card h4 {
    font-size: 1rem;
    margin: 0;
}

.card input:checked + .card-content {
    border: 2px solid #007bff;
    border-radius: 8px;
}
.table {
        margin-top: 20px;
    }
@media (min-width: 1200px) {
        .card {
            padding: 30px;
        }
    }

    .signup-body{
        margin-left: 15px;
    }

    
    </style>
</head>
<body>
<h1 class="main-heading text-center">ALUMNI ELECTION 2024 <br>GCE THANJAVUR</h1>

<div id="container" class="mt-4">
    <?php if ($page == 'login'): ?>
        <h2 class="text-center">Welcome to the Alumni Election Portal!</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Live Voting Update -->
        <?php 
            $voter_count_query = "SELECT COUNT(*) AS total_votes FROM votes";
            $voter_count_result = $conn->query($voter_count_query);
            $voter_count = $voter_count_result->fetch_assoc()['total_votes'];
        ?>
        <p class="text-center fw-bold text-success">
            🗳️ <span class="fs-4">Live Update:</span> 
            <span class="fs-3"><?php echo $voter_count; ?></span> users have voted so far!
        </p>

        <div id="login-body" class="d-flex justify-content-center gap-4">
    <!-- Student Login Section -->
    <div id="studentcard" class="p-4 shadow-sm" style="width: 300px;">
        <h3 class="login-heading text-center mb-3">Student Login</h3>
        <form method="POST">
            <div class="form-group mb-3">
                <label for="student-email" class="form-label">Email</label>
                <input 
                    type="email" 
                    name="email" 
                    id="student-email" 
                    class="form-control" 
                    placeholder="Enter your email" 
                    required>
            </div>
            <div class="form-group mb-3">
                <label for="student-password" class="form-label">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="student-password" 
                    class="form-control" 
                    placeholder="Enter your password" 
                    required>
            </div>
            <div class="text-center">
                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
            </div>
        </form>
        <p class="text-center mt-3">
            <a href="?page=signup">New User? Sign Up Here</a>
        </p>
    </div>

    <!-- Principal Login Section -->
    <div id="principalcard" class="p-4 shadow-sm" style="width: 300px;">
        <h3 class="login-heading text-center mb-3">GCE Election Commission Login</h3>
        <form method="POST">
            <div class="form-group mb-3">
                <label for="principal-username" class="form-label">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    id="principal-username" 
                    class="form-control" 
                    placeholder="Enter your username" 
                    required>
            </div>
            <div class="form-group mb-3">
                <label for="principal-password" class="form-label">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="principal-password" 
                    class="form-control" 
                    placeholder="Enter your password" 
                    required>
            </div>
            <div class="text-center">
                <button type="submit" name="faculty_login" class="btn btn-primary w-100">Login</button>
            </div>
        </form>
    </div>
</div>



<?php elseif ($page == 'signup'): ?>
    <div class="container-fluid mt-5">
        <div class="signup-body" style="width: 500px;">
        <h2 class="text-center">Sign Up for Alumni Election Portal!</h2>

<?php if ($error): ?>
    <div class="alert alert-danger text-center"><?php echo $error; ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success text-center"><?php echo $success; ?></div>
<?php endif; ?>

<form method="POST" class="mx-auto p-4 bg-white rounded shadow" style="max-width: 600px;">
    <div class="form-group mb-3">
        <label for="name" class="form-label">Name</label>
        <input 
            type="text" 
            name="name" 
            id="name" 
            class="form-control" 
            placeholder="Enter your name" 
            required 
            style="background-color: #fff; border: 1px solid #ced4da; color: #495057; padding: 10px;">
    </div>
    <div class="form-group mb-3">
        <label for="gender" class="form-label">Gender</label>
        <select name="gender" id="gender" class="form-select" required>
            <option value="" disabled selected>Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
    </div>
    <div class="form-group mb-3">
        <label for="department" class="form-label">Department</label>
        <select name="department" id="department" class="form-select" required>
            <option value="" disabled selected>Select Department</option>
            <option value="CSE">CSE</option>
            <option value="EEE">EEE</option>
            <option value="ECE">ECE</option>
            <option value="MECH">MECH</option>
            <option value="CIVIL">CIVIL</option>
        </select>
    </div>
    <div class="form-group mb-3">
        <label for="email" class="form-label">Email</label>
        <input 
            type="email" 
            name="email" 
            id="email" 
            class="form-control" 
            placeholder="Enter your email" 
            required 
            style="background-color: #fff; border: 1px solid #ced4da; color: #495057; padding: 10px;">
    </div>
    <div class="form-group mb-3">
        <label for="password" class="form-label">Password</label>
        <input 
            type="password" 
            name="password" 
            id="password" 
            class="form-control" 
            placeholder="Enter your password" 
            required 
            style="background-color: #fff; border: 1px solid #ced4da; color: #495057; padding: 10px;">
    </div>
    <div class="text-center mt-4">
        <button type="submit" name="signup" class="btn btn-primary w-100 py-2">Sign Up</button>
    </div>
    <p class="text-center mt-3">Already have an account? <a href="?page=login">Login</a></p>
</form>


        </div>



            <?php elseif ($page == 'election'): ?>
                <h2>Election Voting</h2>
<form method="POST">
    <!-- Vice President Voting Section -->
    <h3>Vote for Vice President</h3>
    <div class="card-container">
        <?php foreach ($vice_presidents as $vp): ?>
            <?php
            // Create the image path dynamically
            $imagePath = "images/" . strtolower(str_replace(' ', '_', $vp)) . ".png";
            $imageFile = file_exists($imagePath) ? $imagePath : "images/default.png";
            ?>
            <label class="card">
                <input type="radio" name="vice_president" value="<?php echo $vp; ?>" required>
                <div class="card-content">
                    <img src="<?php echo $imageFile; ?>" alt="<?php echo $vp; ?>">
                    <h4><?php echo $vp; ?></h4>
                </div>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- Joint Secretary Voting Section -->
    <h3>Vote for Joint Secretary</h3>
    <div class="card-container">
        <?php foreach ($joint_secretaries as $js): ?>
            <?php
            // Create the image path dynamically
            $imagePath = "images/" . strtolower(str_replace(' ', '_', $js)) . ".jpg";
            $imageFile = file_exists($imagePath) ? $imagePath : "images/";
    

            ?>
            <label class="card">
                <input type="radio" name="joint_secretary" value="<?php echo $js; ?>" required>
                <div class="card-content">
                    <img src="<?php echo $imageFile; ?>" alt="<?php echo $js; ?>">
                    <h4><?php echo $js; ?></h4>
                </div>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- Submit Button -->
    <div class="form-group">
        <input type="submit" name="vote" value="Submit Votes" class="btn btn-primary">
    </div>
</form>


<?php elseif ($page == 'voted'): ?>
    <div class="container-fluid mt-3">
        <div class="card p-4 shadow-lg mx-auto" style="width: 500px;">
            <h2 class="text-success text-center">Thank You for Voting!</h2>
            <p class="fs-4 text-center">You have successfully submitted your votes.</p>
            <div class="mt-4 text-center">
                <p class="fw-bold fs-5">Vice President: <span class="text-primary"><?php echo $_GET['vp']; ?></span></p>
                <p class="fw-bold fs-5">Joint Secretary: <span class="text-primary"><?php echo $_GET['js']; ?></span></p>
            </div>
            <div class="mt-3 text-center">
                <a href="?logout=true" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </div>

<?php elseif ($page == 'results' && isset($_SESSION['is_principal'])): ?>
    <div class="container-fluid mt-5">
        <div class="card shadow-lg p-4 mx-auto" style="width: 500px;">
            <h2 class="text-center text-info">Election Results</h2>
            <hr>
            <div class="mt-3">
                <h3 class="text-primary">Vice President Candidates:</h3>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th class="text-center">Votes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['Vice President'] as $vp): ?>
                            <tr>
                                <td><?php echo $vp['vice_president']; ?></td>
                                <td class="text-center"><?php echo $vp['votes']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <h3 class="text-primary">Joint Secretary Candidates:</h3>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th class="text-center">Votes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['Joint Secretary'] as $js): ?>
                            <tr>
                                <td><?php echo $js['joint_secretary']; ?></td>
                                <td class="text-center"><?php echo $js['votes']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-center">
                <a href="?logout=true" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </div>
<?php endif; ?>


    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>