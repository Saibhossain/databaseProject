<?php
session_start();
//redirect to login.html
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="productCrud.css">
</head>
<body> 
    

    <div class="container">

        <div class="logged"style="text-align: center; margin: 10px;">
            Logged in as: <?php echo htmlspecialchars($_SESSION['username']); ?>
            <a href="logout.php"style="margin-left: 20px;">Log Out</a>
        </div>
        
        <h1>Product CRUD Operations</h1>
        
        <form action="crud.php" method="POST">
            <h3>Create or Update Product</h3>
            <input type="text" name="product_id" placeholder="Product ID (for Update only)" /><br>
            <input type="text" name="product_name" placeholder="Product Name" required /><br>
            <input type="text" name="description" placeholder="Description" /><br>
            <input type="number" name="price" placeholder="Price" required step="0.01" /><br>
            <label for="category">Select Product Category:</label><br>
                <select name="category_id" id="category">
                    <?php
                    require_once 'db_connection.php';
                    $query = "SELECT category_id, category_name FROM Categories";
                    $result = $conn->query($query);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['category_id'] . "'>" . $row['category_name'] . "</option>";
                        }
                    }
                    ?>
                </select><br><br>
            <input type="checkbox" name="is_resold" value="1"> is_resold
            <button type="submit" name="action" value="create">Create</button>
            <button type="submit" name="action" value="update">Update</button>
        </form>

       
        <form action="crud.php" method="POST">
            <h3>Delete Product</h3>
            <input type="number" name="product_id" placeholder="Product ID" required /><br>
            <button type="submit" name="action" value="delete">Delete</button>
        </form>

       
        <form action="crud.php" method="GET">
            <h3>View All Products</h3>
            <button type="submit" name="action" value="readProduct">View Products</button>
        </form>
        <form action="crud.php" method="GET"> 
            <h3>How many products are there?</h3>
            <button type="submit" name="action" value="totalProduct">Count All Product</button>
        </form>
        <form action="crud.php" method="GET"> 
            <h3>How many iPhones are there?</h3>
            <button type="submit" name="action" value="readIphone">Click for Iphone</button>
        </form>
        
    </div>

</body>
</html>
