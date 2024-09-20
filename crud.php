<?php
session_start();
require_once 'db_connection.php';

//function for track user action
function logAdminAction($admin_id, $action_type, $action_description, $conn) {
    $action_date = date('Y-m-d H:i:s'); // Get the current date and time

    $sql = "INSERT INTO AdminActions (admin_id, action_type, action_description, action_date) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isss', $admin_id, $action_type, $action_description, $action_date);
    $stmt->execute();
    $stmt->close();
}

//handling user login (login.html)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'Login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // echo "<h2>$email</h2>";   
    // echo "<h2>$password</h2>";
    // echo "<h2>$is_admin</h2>";

    // Prepare the SQL statement with placeholders
    $sql = "SELECT user_id, username, password, is_admin FROM Users WHERE email = ?";
    
   
    if ($stmt = $conn->prepare($sql)) {
        // Bind the email parameter
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();        // Store the result

        // Check if any row is returned
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $username, $hashed_password, $is_admin_db);  
            $stmt->fetch(); // Fetch the data

            // Verify the password
            if (password_verify($password, $hashed_password)) {
                session_start();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = $is_admin_db;
                $_SESSION['loggedin'] = true;
                echo "Login from admin";
                //sleep(2);
                header("Location: index.php");
            } else {
                echo "Invalid password!";
            }
        } else {
            echo "No user found with this email!";
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
    $conn->close();
}



// Handle user registration (Sign Up)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'Sign_Up') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['cpassword'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;  

    // Validate passwords
    if ($password != $confirm_password) {
        echo "Passwords do not match!";
        exit();
    }

    // Check if the user already exists
    $sql = "SELECT * FROM Users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "User with this email already exists!";
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    
    $sql = "INSERT INTO Users (username, email, password, is_admin) VALUES ('$name', '$email', '$hashed_password', $is_admin)";

    if ($conn->query($sql) === TRUE) {
        echo "Account created successfully!";
        // Redirect the user to the login page
        header("Location: login.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    //Create
    if ($action == 'create') {

        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $is_resold = isset($_POST['is_resold']) ? 1 : 0; // Checkbox for 'is_resold'

        // Insert into Products table
        $sql_product = "INSERT INTO Products (product_name, description, price) 
                        VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql_product);
        $stmt->bind_param('ssd', $product_name, $description, $price);
        $stmt->execute();
        $product_id = $stmt->insert_id; // Get the last inserted product ID

        // Insert into ProductCategory table
        $sql_category = "INSERT INTO ProductCategory (product_id, category_id, is_resold) 
                        VALUES (?, ?, ?)";
        $stmt_category = $conn->prepare($sql_category);
        $stmt_category->bind_param('iii', $product_id, $category_id, $is_resold);
        $stmt_category->execute();

        // Log the action in AdminActions
        $admin_id = $_SESSION['user_id'];
        $action_type = "add";
        $action_description = "Added product: $product_name (Category ID: $category_id, Resold: $is_resold)";
        logAdminAction($admin_id, $action_type, $action_description, $conn);

        echo "Product added successfully!";
        $stmt->close();
        $stmt_category->close();

    } 
     // Update
    elseif ($action == 'update') {
            
        $product_id = $_POST['product_id'];
        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $is_resold = isset($_POST['is_resold']) ? 1 : 0;

        // Update the Products table
        $sql_product = "UPDATE Products SET product_name = ?, description = ?, price = ?
                        WHERE product_id = ?";
        $stmt = $conn->prepare($sql_product);
        $stmt->bind_param('ssdi', $product_name, $description, $price, $product_id);
        $stmt->execute();

        // Update the ProductCategory table
        $sql_category = "UPDATE ProductCategory SET category_id = ?, is_resold = ?
                        WHERE product_id = ?";
        $stmt_category = $conn->prepare($sql_category);
        $stmt_category->bind_param('iii', $category_id, $is_resold, $product_id);
        $stmt_category->execute();

        // Log the action in AdminActions
        $admin_id = $_SESSION['user_id'];
        $action_type = "update";
        $action_description = "Updated product: $product_name (Category ID: $category_id, Resold: $is_resold)";
        logAdminAction($admin_id, $action_type, $action_description, $conn);

        echo "Product updated successfully!";
        $stmt->close();
        $stmt_category->close();

    } //Delete
    elseif ($action == 'delete') {
        $product_id = $_POST['product_id'];

        // Delete from ProductCategory table
        $sql_category = "DELETE FROM ProductCategory WHERE product_id = ?";
        $stmt_category = $conn->prepare($sql_category);
        $stmt_category->bind_param('i', $product_id);
        $stmt_category->execute();

        // Delete from Products table
        $sql_product = "DELETE FROM Products WHERE product_id = ?";
        $stmt = $conn->prepare($sql_product);
        $stmt->bind_param('i', $product_id);
        $stmt->execute();

        // Log the action in AdminActions
        $admin_id = $_SESSION['user_id'];
        $action_type = "delete";
        $action_description = "Deleted product with ID: $product_id";
        logAdminAction($admin_id, $action_type, $action_description, $conn);

        echo "Product deleted successfully!";
        $stmt->close();
        $stmt_category->close();
    }

    
}


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'];

    if($action == 'readProduct'){

        $sql = "SELECT * FROM Products";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<h2>Product List</h2>";
            while ($row = $result->fetch_assoc()) {
                echo "ID: " . $row["product_id"] . " - Name: " . $row["product_name"] . " - Price: $" . $row["price"] . "<br>";
            }
            
        } else {
            echo "No products found.";
        }
    }elseif($action == 'totalProduct'){

        $sql = "SELECT COUNT(product_id) as total from products";
        $result = $conn->query($sql);
        

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
        echo "<br><h2>The Number of total products is:-   " . $row['total'] . "</h2>";
        } else {
            echo "Some ERROR detected ! ";
        }
    }elseif($action == 'readIphone'){

        $sql = "SELECT * FROM products where product_name LIKE 'iphone%'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<h2>Product List</h2>";
            while ($row = $result->fetch_assoc()) {
                echo "ID: " . $row["product_id"] . " --- Name: " . $row["product_name"] . " --- Date: $" . $row["created_at"] . "<br>";
            }
        } else {
            echo "No Iphone found.";
        }
    }
}

$conn->close();
?>
