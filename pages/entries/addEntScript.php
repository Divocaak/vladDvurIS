<?php
require_once "../../config.php";

$e = "";
$minutes = ((floatval($_POST["hours"]) * 60) + floatval($_POST["minutes"]));
if($minutes > 0){
    $sql = "INSERT INTO entry (id_category, id_contract, date, minutes) VALUES (" . $_POST["tagSelect"] . ", '" . $_POST["cont"] . "', '" . $_POST["date"] . "', " . $minutes . ");";
    if (!mysqli_query($link, $sql)) {
        $e = $sql . "<br>" . mysqli_error($link);
    }
}else{
    $e = "Nelze přidat zápis, kde se počet odpracovaných minut rovná nule.";
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
    <p><?php echo $e == "" ? '<i class="pe-2 bi bi-check-circle-fill text-success"></i>Záznam byl přidán' : ('<i class="pe-2 bi bi-exclamation-circle-fill text-danger"></i>' . $e) ?></p>
    <a class="btn btn-outline-secondary" href="entList.php"><i class="pe-2 bi bi-arrow-left-circle"></i>Přejít na tabulku</a>
</body>

</html>