<?php
include("../includes/auth.php");
checkLogin();
checkRole('admin');

include("../includes/db.php");
include("../includes/header.php");

if(!isset($_GET['id'])){
    header("Location: dashboard.php");
    exit();
}

$course_id = intval($_GET['id']);

/* ===============================
   FETCH COURSE DATA
=================================*/
$stmt = $conn->prepare("SELECT * FROM courses WHERE id=?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if(!$course){
    echo "Course not found.";
    exit();
}

/* ===============================
   UPDATE COURSE
=================================*/
if(isset($_POST['update'])){

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $instructor_id = intval($_POST['instructor_id']);

    $imageName = $course['image']; // keep old image by default

    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){

        // Delete old image
        if(!empty($course['image'])){
            $oldPath = "../uploads/" . $course['image'];
            if(file_exists($oldPath)){
                unlink($oldPath);
            }
        }

        $imageName = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/".$imageName);
    }

    $update = $conn->prepare("
        UPDATE courses 
        SET title=?, description=?, price=?, instructor_id=?, image=? 
        WHERE id=?
    ");

    $update->bind_param("ssdisi", 
        $title, 
        $description, 
        $price, 
        $instructor_id, 
        $imageName,
        $course_id
    );

    $update->execute();

    header("Location: dashboard.php");
    exit();
}
?>

<h2>Edit Course</h2>

<form method="POST" enctype="multipart/form-data">

Title:
<input type="text" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>

Description:
<textarea name="description" required><?php echo htmlspecialchars($course['description']); ?></textarea>

Price:
<input type="number" step="0.01" name="price" value="<?php echo $course['price']; ?>" required>

Instructor:
<select name="instructor_id" required>
<?php
$instructors = $conn->query("SELECT id,name FROM users WHERE role='instructor'");
while($row = $instructors->fetch_assoc()){
    $selected = ($row['id'] == $course['instructor_id']) ? "selected" : "";
    echo "<option value='".$row['id']."' $selected>".$row['name']."</option>";
}
?>
</select>

Current Image:<br>
<?php if(!empty($course['image'])){ ?>
    <img src="../uploads/<?php echo $course['image']; ?>" width="150"><br><br>
<?php } ?>

Change Image:
<input type="file" name="image">

<input type="submit" name="update" value="Update Course">

</form>

<?php include("../includes/footer.php"); ?>