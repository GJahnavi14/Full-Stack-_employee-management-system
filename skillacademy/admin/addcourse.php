<?php
include("../includes/auth.php");
checkLogin();
checkRole('admin');

include("../includes/db.php");
include("../includes/header.php");

if(isset($_POST['create'])){

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $instructor_id = intval($_POST['instructor_id']);

    $imageName = "";
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
        $imageName = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/".$imageName);
    }

    $stmt = $conn->prepare("
        INSERT INTO courses (title, description, price, instructor_id, image)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssdis", $title, $description, $price, $instructor_id, $imageName);
    $stmt->execute();

    header("Location: dashboard.php");
    exit();
}
?>

<h2>Create Course</h2>

<form method="POST" enctype="multipart/form-data">

Title:
<input type="text" name="title" required>

Description:
<textarea name="description" required></textarea>

Price:
<input type="number" step="0.01" name="price" required>

Instructor:
<select name="instructor_id" required>
<?php
$instructors = $conn->query("SELECT id,name FROM users WHERE role='instructor'");
while($row = $instructors->fetch_assoc()){
    echo "<option value='".$row['id']."'>".$row['name']."</option>";
}
?>
</select>

Image:
<input type="file" name="image">

<input type="submit" name="create" value="Create Course">

</form>

<?php include("../includes/footer.php"); ?>