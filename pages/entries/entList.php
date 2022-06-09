<?php
require_once "../../config.php";
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Zápis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
</head>

<body class="text-center m-5 p-5">
    <div class="pb-3">
        <a class="btn btn-outline-secondary" href="../../index.php"><i class="pe-2 bi bi-arrow-left-circle"></i>Zpět</a>
        <h1 class="d-inline-block ms-2">Zápis</h1>
    </div>
    <form class="needs-validation" novalidate action="addContScript.php" method="post">
        <div class="row">
            <div class="col">
                <div class="form-floating mb-3">
                    <select class="form-select form-control" id="emp" name="emp" required>
                        <?php
                        $sql = "SELECT id, f_name, m_name, l_name, b_date FROM employee;";
                        if ($result = mysqli_query($link, $sql)) {
                            while ($row = mysqli_fetch_row($result)) {
                                echo "<option value=" . $row[0] . ">" . $row[1] . ($row[2] != "" ? (" " . $row[2]) : "") . " " . $row[3] . " (*" . $row[4] . ")</option>";
                            }
                            mysqli_free_result($result);
                        } else {
                            echo "<option selected>Někde se stala chyba</option>";
                        }
                        ?>
                    </select>
                    <label for="emp">Zaměstnanec</label>
                </div>
            </div>
            <div class="col">
                <div class="form-floating mb-3">
                    <select class="form-select form-control" id="month" name="month" required>
                        <?php
                        $m = ["leden", "únor", "březen", "duben", "květen", "červen", "červenec", "srpen", "září", "říjen", "listopad", "prosinec"];
                        for ($i = 0; $i < count($m); $i++) {
                            echo "<option value=" . ($i + 1) . (($i + 1) == date("m") ? " selected" : "") . ">" . $m[$i] . "</option>";
                        }
                        ?>
                    </select>
                    <label for="month">Měsíc</label>
                </div>
            </div>
            <div class="col">
                <div class="form-floating mb-3">
                    <input type="number" class="form-control" id="year" name="year" required value="<?php echo date("Y"); ?>">
                    <label for="year">Rok</label>
                </div>
            </div>
            <div class="col">
                <a id="selectEmpBtn" class="btn btn-outline-primary"><i class="pe-1 bi bi-person-bounding-box"></i><i class="pe-2 bi bi-calendar-month"></i>Vybrat zaměstnance a měsíc</a>
            </div>
        </div>
    </form>

    <?php
    $month = 5;
    $year = 2022;

    $sql = "SELECT e.id, e.date, e.minutes, def.name, def.value, def.color, c.id, c.max_hours, c.max_cash, c.note, d.label, d.date_start, d.date_end, d.cash_rate 
            FROM entry e LEFT JOIN defaults def ON e.id_category=def.name INNER JOIN contract c ON e.id_contract=c.id INNER JOIN document d ON c.id_document=d.id
            WHERE YEAR(e.date)=" . $year . " AND MONTH(e.date)=" . $month . ";";
    if ($result = mysqli_query($link, $sql)) {
        $entries = [];
        $contracts = [];
        while ($row = mysqli_fetch_row($result)) {
            $entries[$row[0]] = [
                "id" => $row[0],
                "date" => $row[1],
                "minutes" => $row[2],
                "tag" => [
                    "id" => $row[3],
                    "label" => $row[4],
                    "color" => $row[5]
                ],
                "contract" => [
                    "id" => $row[6],
                    "maxHours" => $row[7],
                    "maxCash" => $row[8],
                    "note" => $row[9]
                ],
                "document" => [
                    "label" => $row[10],
                    "start" => $row[11],
                    "end" => $row[12],
                    "cashRate" => $row[13]
                ]
            ];

            if (!in_array($row[6], $contracts)) {
                $contracts[] = $row[6];
            }
        }
        mysqli_free_result($result);
        $_SESSION["entries"] = $entries;
    } else {
        echo "eee";
    }
    ?>

    <table class="mt-3 table table-striped table-hover">
        <caption>Zápisy</caption>
        <thead class="table-dark">
            <tr>
                <th scope="col">#</th>
                <?php
                sort($contracts);
                foreach ($contracts as $contract) {
                    echo "<th scope='col'>" . $contract . "</th>";
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            for ($day = 1; $day < cal_days_in_month(CAL_GREGORIAN, $month, $year) + 1; $day++) {
                $rowHead = '<th scope="row">' . $day . "." . $month . '.</th>';
                $cellTag = "";
                $cellContent = "";
                foreach ($contracts as $contract) {
                    $hasEntry = false;

                    $cellTag .= "<td data-day='" . $day . "' data-contract-id='" . $contract . "'";
                    foreach ($entries as $entryId => $entry) {
                        if (DateTime::createFromFormat("Y-m-d", $entry["date"])->format("d") == $day && $entry["contract"]["id"] == $contract) {
                            $hasEntry = true;
                            $cellTag .= " data-entry-id='" . $entryId . "'";
                            $cellContent .= date('H:i', mktime(0, $entry["minutes"]));
                            $cellContent .= (isset($entry["tag"]["label"]) ? ('<span class="ms-2 badge rounded-pill" style="background-color:#' . $entry["tag"]["color"] . ';">' . $entry["tag"]["label"] . "</span>") : "") . "<br>";
                            $cellContent .= '<a class="mt-2 me-2 btn btn-outline-primary editBtn actionIdBtn"><i class="bi bi-pencil"></i></a>';
                            $cellContent .= '<a class="mt-2 btn btn-outline-danger deleteBtn actionIdBtn"><i class="bi bi-trash"></i></a>';
                        }
                    }
                    $cellTag .= ">";

                    if (!$hasEntry) {
                        $cellContent .= '<a class="mt-2 btn btn-outline-success addBtn"><i class="bi bi-plus"></i></a>';
                    }

                    $cellContent .= "</td>";
                }
                echo '<tr>' . $rowHead . $cellTag . $cellContent . "</tr>";
            }
            ?>
        </tbody>
    </table>
    <div class="modal fade" id="confDeleteModal" tabindex="-1" aria-labelledby="confDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Opravdu?</h5>
                </div>
                <div class="modal-body">
                    Skutečně chcete odstranit záznam ze systému?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zavřít</button>
                    <button type="button" class="btn btn-outline-danger" id="confDeleteBtn">Odstranit</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="entryFormModal" tabindex="-1" aria-labelledby="entryFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Zápis</h5>
                </div>
                <div class="modal-body">
                    <form class="needs-validation" novalidate action="editEntScript.php" method="post" id="entFormHeader">
                        <div class="row">
                            <div class="col">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="id" name="id" readonly value="">
                                    <label for="id">ID</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating mb-3 p-2">
                                    <input type="number" class="form-control" id="hours" name="hours" required value="">
                                    <label for="hours">Hodiny</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating mb-3 p-2">
                                    <input type="number" class="form-control" id="minutes" name="minutes" required value="">
                                    <label for="minutes">Minuty</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating mb-3 p-2 rounded" id="selectedTagIndicator" style="background-color: transparent;">
                                    <select class="form-select form-control" id="tagSelect" name="tagSelect">
                                        <?php
                                        $sql = "SELECT name, value, color FROM defaults WHERE status=1 AND color IS NOT NULL;";
                                        if ($result = mysqli_query($link, $sql)) {
                                            echo "<option value='NULL' data-color='FFFFFF' selected>-</option>";
                                            while ($row = mysqli_fetch_row($result)) {
                                                echo "<option value=" . $row[0] . " data-color='" . $row[2] . "'>" . $row[1] . "</option>";
                                            }
                                            mysqli_free_result($result);
                                        } else {
                                            echo "<option selected>Někde se stala chyba</option>";
                                        }
                                        ?>
                                    </select>
                                    <label for="tagSelect">Značka</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="" id="entryFormSaveBtn"></button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zavřít</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            var day;
            var contId;
            $(".addBtn").click(function() {
                day = $(this).parent().data("day");
                contId = $(this).parent().data("contractId");

                showEntryForm(false);
            });

            var entId;
            $(".actionIdBtn").click(function() {
                entId = $(this).parent().data("entryId");
            });

            $(".editBtn").click(function() {
                showEntryForm(true, entId);
            });

            $(".deleteBtn").click(function() {
                $('#confDeleteModal').modal('show');
            });

            $("#confDeleteBtn").click(function() {
                window.location = "delEntScript?id=" + entId;
            });

            $("#tagSelect").change(function() {
                $("#selectedTagIndicator").css('background-color', ("#" + $("#tagSelect option:selected").data("color")));
            });
        });

        function showEntryForm(isEdit, entId) {
            $("#entryFormSaveBtn").attr('class', ("btn btn-outline-" + (isEdit ? "primary" : "success")));
            $("#entryFormSaveBtn").text(isEdit ? "Uložit" : "Přidat");
            $("#entFormHeader").attr("action", ((isEdit ? "edit" : "add") + "EntScript.php"));

            if(isEdit){
                $.post("getEntData.php", {
                    index: entId
                }, function(data) {
                    var dataDecoded = JSON.parse(data);
                    entFormValues(dataDecoded["id"], Math.floor(dataDecoded["minutes"] / 60), (dataDecoded["minutes"] % 60), dataDecoded["tag"]["id"])
                });
            }else{
                entFormValues("-", "", "", "NULL");
            }
            
            $('#entryFormModal').modal('show');
        }
        
        function entFormValues(id, hours, minutes, tagId){
            $("#id").val(id);
            $("#hours").val(hours);
            $("#minutes").val(minutes);
            $("#tagSelect").val(tagId);
            $("#selectedTagIndicator").css('background-color', ("#" + $("#tagSelect option:selected").data("color")));
        }
    </script>
</body>

</html>