<?php
include("../includes/auth.php");
checkLogin();
checkRole('admin');

include("../includes/db.php");
include("../includes/header.php");

// Delete Course
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
}

// Fetch Courses
$result = $conn->query("SELECT * FROM courses");
?>

<h2>Manage Courses</h2>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Price</th>
    <th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>
<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['title']; ?></td>
    <td>₹<?php echo $row['price']; ?></td>
    <td>
        <a href="?delete=<?php echo $row['id']; ?>">Delete</a>
    </td>
</tr>
<?php } ?>

</table>

<?php include("../includes/footer.php"); ?>