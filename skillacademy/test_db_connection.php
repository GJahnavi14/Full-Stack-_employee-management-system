<?php
echo "<h2>Testing Database Connection</h2>";

// Test with port 3307
try {
    $pdo = new PDO("mysql:host=localhost;port=3307;dbname=skillacademy", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span style='color:green'>✓ Connected to skillacademy database on port 3307!</span><br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM courses");
    $result = $stmt->fetch();
    echo "✓ Total courses: " . $result['count'] . "<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Total users: " . $result['count'] . "<br>";
    
    echo "<br><span style='color:green'>Database is working correctly!</span>";
    
} catch(PDOException $e) {
    echo "<span style='color:red'>✗ Connection failed: " . $e->getMessage() . "</span><br>";
    
    // Try without database name
    echo "<br>Testing without database name...<br>";
    try {
        $pdo = new PDO("mysql:host=localhost;port=3307", "root", "");
        echo "<span style='color:green'>✓ Can connect to MySQL server on port 3307</span><br>";
        
        // List databases
        $stmt = $pdo->query("SHOW DATABASES");
        echo "<br>Available databases:<br>";
        while($row = $stmt->fetch()) {
            echo "- " . $row[0] . "<br>";
        }
    } catch(PDOException $e2) {
        echo "<span style='color:red'>✗ Cannot connect to MySQL: " . $e2->getMessage() . "</span>";
    }
}
?>