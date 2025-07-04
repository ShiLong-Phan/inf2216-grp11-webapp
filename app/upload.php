<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'dbConfig.php'; 
$statusMsg = ''; 
$targetDir = "uploads/";

// Ensure uploads folder exists
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if(isset($_POST["submit"])){ 
    if(!empty($_FILES["file"]["name"])){ 
        $fileName = basename($_FILES["file"]["name"]); 
        $targetFilePath = $targetDir . $fileName; 
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION); 
 
        $allowTypes = array('jpg','png','jpeg','gif'); 
        if(in_array($fileType, $allowTypes)){ 
            if(move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)){ 
                // Example product details (replace with actual form input)
                $prod_name = 'Test Product';
                $prod_price = 99.99;
                $prod_img = $fileName;

                // Insert into products table
                $insert = $db->query("INSERT INTO products (prod_name, prod_price, prod_img) 
                                      VALUES ('$prod_name', '$prod_price', '$prod_img')");
                if($insert){ 
                    $statusMsg = "The file ".$fileName. " has been uploaded and saved in database."; 
                } else { 
                    $statusMsg = "Database insert failed."; 
                }  
            } else { 
                $statusMsg = "File upload failed."; 
            } 
        } else { 
            $statusMsg = "Only JPG, JPEG, PNG, GIF files are allowed."; 
        } 
    } else { 
        $statusMsg = "Please select a file."; 
    } 
}

echo $statusMsg; 
?>
