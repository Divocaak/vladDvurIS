<?php
require_once "../config.php";

$e = "";
$sql = "UPDATE employee SET f_name='" . $_POST["fname"] . "', m_name=" . ($_POST["mname"] != "" ? ("'" . $_POST["mname"] . "'") : "NULL") . ", l_name='" . $_POST["lname"] . "', b_date='" . $_POST["bdate"] . "', student=" . (isset($_POST["student"]) ? "1" : "0") . ", maternity=" . (isset($_POST["maternity"]) ? "1" : "0") . ", hpp=" . (isset($_POST["hpp"]) ? "1" : "0") . "
WHERE id=" . $_GET["empId"] . ";";
if (!mysqli_query($link, $sql)) {
    $e = $sql . "<br>" . mysqli_error($link);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Odpověď ze serveru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
</head>

<body class="text-center m-5 p-5">
    <h1 class="pb-3 ms-2">Odpověď ze serveru</h1>
    <p><?php echo $e == "" ? '<i class="pe-2 bi bi-check-circle-fill text-success"></i>Zaměstnanec byl upraven' : ('<i class="pe-2 bi bi-exclamation-circle-fill text-danger"></i>' . $e) ?></p>
    <a class="btn btn-outline-secondary" href="empList.php"><i class="pe-2 bi bi-arrow-left-circle"></i>Přejít na seznam zaměstnanců</a>
</body>

</html>