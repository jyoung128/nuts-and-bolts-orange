<?php
    session_start();

    if(isset($_SESSION['isUser']) || isset($_SESSION['isEmployee'])){
        $userLoggedIn = $_SESSION['isUser'];
        $employeeLoggedIn = $_SESSION['isEmployee'];
    } else {
        $userLoggedIn = false;
        $employeeLoggedIn = false;
    }
?>
<?php
    require_once "config/connect.php";

    function searchForSKU($id, $array) {
        foreach ($array as $key => $val) {
            if ($val['sku'] === $id) {
                return true;
            }
        }
        return false;
    }

    $name = '';
    $sku = '';
    $desc = '';
    $price = '';
    $selectSKU = '';
    $result = mysqli_query($conn, "SELECT * FROM inventory LIMIT 0,0");
    $rows = array();

    $errors = array('name'=>'', 'sku'=>'', 'desc'=>'', 'price'=>'');
    
    if (isset($_SESSION['isEmployee']) && $_SESSION['isEmployee'] == true) {
        ;
    } else {
        $_SESSION['loginmessage'] = True;
        header("location: login.php");
    }

    if(isset($_SESSION['isUser']) || isset($_SESSION['isEmployee'])){
        $userLoggedIn = $_SESSION['isUser'];
        $employeeLoggedIn = $_SESSION['isEmployee'];
    } else {
        $userLoggedIn = false;
        $employeeLoggedIn = false;
    }

    if(isset($_POST['select'])) {
        $_SESSION['sku'] = $_POST['select'];
        echo json_encode(mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM inventory WHERE sku='".$_POST['select']."'")));
        exit();
    }

    if(isset($_POST['find'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $sku = mysqli_real_escape_string($conn, $_POST['sku']);

        $result = mysqli_query($conn, "SELECT * FROM inventory WHERE product_name='$name' OR sku='$sku'");

        if(mysqli_num_rows($result) > 0) {
            
            while($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }

            if(count($rows) == 1) {
                $name = $rows[0]['product_name'];
                $sku = $rows[0]['sku'];
                $_SESSION['sku'] = $rows[0]['sku'];
                $desc = $rows[0]['description'];
                $price = $rows[0]['price'];
            }
        }
    }

    if(isset($_POST['update'])) {
        if(empty($_POST['name'])) {
            $errors['name'] = 'A product name is required';
        } else {
            $name = $_POST['name'];
            if(!preg_match('/^[\s\S]{1,255}$/', $name)){
                $errors['name'] = 'The product name must be no longer than 255 characters';
            }
        }

        if(empty($_POST['sku'])) {
            $errors['sku'] = 'A product SKU is required';
        } else {
            $sku = $_POST['sku'];
            if(!preg_match('/^[0-9A-Z]{1,12}$/', $sku)){
                $errors['sku'] = 'The product SKU must be no longer than 12 capital alphanumeric characters';
            } else if(mysqli_fetch_assoc(mysqli_query($conn, "SELECT sku FROM inventory WHERE sku='$sku'"))["sku"] == $sku && searchForSKU($sku, $rows)){
                $errors['sku'] = 'This SKU already exists';
            }
        }

        if(empty($_POST['desc'])) {
            $errors['desc'] = 'A product description is required';
        } else {
            $desc = $_POST['desc'];
            if(!preg_match('/^[\s\S]{1,255}$/', $desc)){
                $errors['desc'] = 'The product description must be no longer than 255 characters';
            }
        }

        if(empty($_POST['price'])) {
            $errors['price'] = 'A product price is required';
        } else {
            $price = $_POST['price'];
            if(!is_numeric($price)){
                $errors['price'] = 'The product price must be a numeric value';
            }
        }

        if(!array_filter($errors)) {
            $stmt = $conn->prepare("UPDATE inventory SET product_name=?, sku=?, description=?, price=? WHERE sku=?");
            $stmt->bind_param("sssds", $name, $sku, $desc, $price, $selectSKU);

            $name = mysqli_real_escape_string($conn, $_POST['name']);
            $sku = mysqli_real_escape_string($conn, $_POST['sku']);
            $desc = mysqli_real_escape_string($conn, $_POST['desc']);
            $price = mysqli_real_escape_string($conn, $_POST['price']);
            $selectSKU = $_SESSION['sku'];

            $name = stripslashes($name);
            $sku = stripslashes($sku);
            $desc = stripslashes($desc);
            $price = stripslashes($price);

            if($stmt->execute()) {
                $_SESSION['updateStatus'] = true;
                unset($_SESSION['sku']);
                header("Location: {$_SERVER['REQUEST_URI']}", true, 303);
                $stmt->close();
                $conn->close();
                exit();
            }
        } else {
            $_SESSION['updateStatus'] = false;
        }
    }
?>
<?php require_once "include/header.php"; ?>
        
        <script>
            $(document).ready(function(){
                $("button[class='btn btn-primary select']").click(function(){
                    $.ajax({
                        type: 'post',
                        context: this,
                        data: {select: $(this).val()},
                        success: function(response){
                            var row = jQuery.parseJSON(response);
                            $("#productName").val(row.product_name);
                            $("#productSKU").val(row.sku);
                            $("#productPrice").val(row.price);
                            $("#productDescription").text(row.description);
                        }
                    });
                });
            });
        </script>

        <title>Update Products | Nuts and Bolts</title>

    </head>
    <body>

        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="index.php">Nuts and Bolts</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="navbar-nav">
                        <a class="nav-link" href="index.php">Home</a>
                        <a class="nav-link" href="products.php">Products</a>
                        <a class="nav-link" href="add.php">Add Products</a>
                        <a class="nav-link active" aria-current="page" href="update.php">Update Products</a>
                        <a class="nav-link" href="faq.php">FAQ</a>
                        <a class="nav-link" href="contact.php">Contact Us</a>
                    
                    </div>
                    <div class="navbar-nav ms-auto flex-nowrap">
                    <?php if($userLoggedIn): ?>
                        <?php echo '<span class="nav-link">'. $_SESSION['username'] . '</span>' ?>
                        <a class="nav-link" href="history.php">Order History</a>
                        <span class="collapse show nav-link" id="navbarNavAltMarkup">|</span>
                        <a class="nav-link" href="logout.php">Logout</a>
                    <?php elseif($employeeLoggedIn): ?>
                        <?php echo '<span class="nav-link">'. $_SESSION['firstname'] . '</span>' ?>
                        <span class="collapse show nav-link" id="navbarNavAltMarkup">|</span>
                        <a class="nav-link" href="logout.php">Logout</a>
                    <?php else: ?>
                        <a class="nav-link" href="register.php">Register</a>
                        <span class="collapse show nav-link" id="navbarNavAltMarkup">|</span>
                        <a class="nav-link" href="login.php">Login</a>
                    <?php endif; ?>
                    <a class="nav-link" href="cart.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart4" viewBox="0 0 16 16">
                        <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5zM3.14 5l.5 2H5V5H3.14zM6 5v2h2V5H6zm3 0v2h2V5H9zm3 0v2h1.36l.5-2H12zm1.11 3H12v2h.61l.5-2zM11 8H9v2h2V8zM8 8H6v2h2V8zM5 8H3.89l.5 2H5V8zm0 5a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0zm9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0z"/></svg>
                    </a>
                    </div>
                </div>
            </div>
        </nav>
        
        <div class="container">
            <h1>Update Products</h1>
            <div class="container bg-light text-dark">
                <form class="row g-3" action="update.php" method="POST">
                   
                    <div class="form-group col-12">
                        <label for="productName" class="form-label">Product Name:</label>
                        <input type ="text" class="form-control" id="productName" name ="name" value="<?php echo htmlspecialchars($name); ?>">
                        <span class="text-danger">
                            <?php echo $errors['name']; ?>
                        </span>
                    </div>
                    
                    <div class="form-group <?php if(mysqli_num_rows($result) == 0 && !isset($_POST['update'])) { echo "col-12";} else { echo "col-md-6";}?>">
                        <label for="productSKU" class="form-label">SKU:</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text">#</span>
                            <input type ="text" class="form-control" id="productSKU" name ="sku" value="<?php echo htmlspecialchars($sku); ?>">
                        </div>
                        <span class="text-danger">
                            <?php echo $errors['sku']; ?>
                        </span>
                    </div>

                    <?php                
                        if(mysqli_num_rows($result) == 0 && !isset($_POST['update'])) {
                    ?>
                        
                        <button class="btn btn-primary" type="submit" name="find">Find Product</button>
                        
                        <?php
                            if(isset($_POST['find']) && mysqli_num_rows($result) == 0) {
                        ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php if($name =='') { echo 'This item';}else{ echo htmlspecialchars($name);} ?> could not be found.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php
                            }
                        ?>
                    
                    <?php
                        }
                    ?>
                    
                    <?php                
                        if(mysqli_num_rows($result) > 0 || (isset($_SESSION['updateStatus']) && !$_SESSION['updateStatus'])) {
                    ?>
                        
                        <div class="form-group col-md-6">
                            <label for="productPrice" class="form-label">Price:</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text">$ </span>
                                <input type="text" class="form-control" id="productPrice" name="price" value="<?php echo htmlspecialchars($price); ?>">
                            </div>
                            <span class="text-danger">
                                <?php echo $errors['price']; ?>
                            </span>
                        </div>
                        
                        <div class="form-group col-12">
                            <label for="productDescription" class="form-label">Description:</label>
                            <textarea class="form-control" id="productDescription" name="desc" rows="4" cols="50"><?php echo htmlspecialchars($desc); ?></textarea>
                            <span class="text-danger">
                                <?php echo $errors['desc']; ?>
                            </span>
                        </div>
                        
                        <?php                
                            if(count($rows) > 1 && !isset($_POST['update'])) {
                        ?>
                            <h5>Multiple entries exist (Please select one):</h5>
                            <?php 
                            echo '<div class = "row row-cols-1 row-cols-md-4 g-3">';
                            for($item = 0; $item < count($rows); $item++) {
                                echo '<div class = "col">
                                    <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">' . $rows[$item]['product_name'] . '</h5>
                                                <p class="card-text"><small class = "text-muted">SKU: ' . $rows[$item]['sku'] . '</small></p>
                                            </div>
                                            <ul class="h-100 list-group list-group-flush">
                                                <li class="list-group-item">' . $rows[$item]['description'] . '</li>
                                            </ul>
                                            <div class="card-body">
                                                <p class="card-text">$' . $rows[$item]['price'] . '</p>
                                                <button class="btn btn-primary select" type="button" value="'.$rows[$item]['sku'].'">Select</button>
                                            </div>
                                        </div>
                                    </div>';
                                }
                            echo "</div>";
                        }
                        ?>

                        <button class="btn btn-primary" type="submit" name="update">Update</button>
                        
                        <?php
                            if(isset($_SESSION['updateStatus']) && !$_SESSION['updateStatus']) {
                        ?>
                            
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php if($name =='') { echo 'This item';}else{ echo htmlspecialchars($name);} ?> could not be updated due to an error.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        
                        <?php
                            }
                            unset($_SESSION['updateStatus']);
                        ?>
                    
                    <?php
                        }              
                        if(isset($_SESSION['updateStatus']) && $_SESSION['updateStatus']) {
                    ?>
                        
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Item was updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    
                    <?php
                        unset($_SESSION['updateStatus']);
                        }
                    ?>
                </form>
            </div>
        </div>
    
<?php require_once "include/footer.php"; ?>