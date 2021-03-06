<?php
require_once "../../config.php";

$reportContract = [];
$sql = "SELECT id_contract, to_pay, real_hours, real_to_pay, resolved FROM report_contract WHERE month=" . $_POST["month"] . " AND year=" . $_POST["year"] . ";";
if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_row($result)) {
        $reportContract[$row[0]] = [
            "toPay" => $row[1],
            "realHours" => $row[2],
            "realToPay" => $row[3],
            "resolved" => $row[4]
        ];
    }
    mysqli_free_result($result);
}

$reportEmployee = [];
$sql = "SELECT id_employee, additional_payment, resolved FROM report_employee WHERE month=" . $_POST["month"] . " AND year=" . $_POST["year"] . ";";
if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_row($result)) {
        $reportEmployee[$row[0]] = [
            "additionalPayment" => $row[1],
            "resolved" => $row[2]
        ];
    }
    mysqli_free_result($result);
}

$reportData = [];
$sql = "SELECT c.id_employee, c.max_hours, c.max_cash, SUM(e.minutes), c.id, d.label, d.cash_rate, em.f_name, em.m_name, em.l_name
        FROM contract c LEFT JOIN entry e ON e.id_contract=c.id RIGHT JOIN document d ON c.id_document=d.id INNER JOIN employee em ON em.id=c.id_employee
        WHERE YEAR(e.date)=" . $_POST["year"] . " AND MONTH(e.date)=" . $_POST["month"] . "
        GROUP BY c.id_employee, c.id, c.max_cash, c.max_hours, em.f_name, em.l_name, em.m_name;";
if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_row($result)) {
        if (!isset($reportData[$row[0]]["additionalPayment"]) && !isset($reportData[$row[0]]["resolved"])) {
            $reportData[$row[0]] = (isset($reportEmployee[$row[0]]) ? $reportEmployee[$row[0]] : ["additionalPayment" => 0, "resolved" => false]);
        }

        if (!isset($reportData[$row[0]]["name"])) {
            $reportData[$row[0]]["name"] = ($row[7] . (isset($row[8]) ? " " . $row[8] : "") . " " . $row[9]);
        }

        $reportData[$row[0]]["contracts"][$row[4]] = [
            "maxHours" => $row[1],
            "maxCash" => $row[2],
            "minutes" => $row[3],
            "label" => $row[5],
            "cashRate" => $row[6]
        ];
        $reportData[$row[0]]["contracts"][$row[4]] += (isset($reportContract[$row[4]]) ? $reportContract[$row[4]] : ["toPay" => 0, "realHours" => 0, "realToPay" => 0, "resolved" => false]);
    }
    mysqli_free_result($result);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Souhrn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.1/font/bootstrap-icons.css">
</head>

<body class="text-center m-5 p-5">
    <div class="pb-3">
        <a class="btn btn-outline-secondary" href="../../index.php"><i class="pe-2 bi bi-arrow-left-circle"></i>Zp??t</a>
        <h1 class="d-inline-block ms-2">Souhrn</h1>
    </div>
    <form class="needs-validation" novalidate action="?" method="post">
        <div class="row">
            <div class="col">
                <div class="form-floating mb-3">
                    <select class="form-select form-control" id="month" name="month" required>
                        <?php
                        $defVal = isset($_POST["month"]) ? $_POST["month"] : date("m");
                        $m = ["leden", "??nor", "b??ezen", "duben", "kv??ten", "??erven", "??ervenec", "srpen", "z??????", "????jen", "listopad", "prosinec"];
                        for ($i = 0; $i < count($m); $i++) {
                            echo "<option value=" . ($i + 1) . ($defVal == ($i + 1) ? " selected" : "") . ">" . $m[$i] . "</option>";
                        }
                        ?>
                    </select>
                    <label for="month">M??s??c</label>
                </div>
            </div>
            <div class="col">
                <div class="form-floating mb-3">
                    <input type="number" class="form-control" id="year" name="year" required value="<?php echo isset($_POST["year"]) ? $_POST["year"] : date("Y"); ?>">
                    <label for="year">Rok</label>
                </div>
            </div>
            <div class="col">
                <button type="submit" class="btn btn-outline-primary"><i class="pe-2 bi bi-calendar-month"></i>Vybrat m??s??c</button>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table id="mainTable" class="mt-3 table" data-year="<?php echo $_POST["year"]; ?>" data-month="<?php echo $_POST["month"]; ?>">
            <caption>Souhrn</caption>
            <thead class="table-dark">
                <tr>
                    <th scope="col">Jm??no</th>
                    <th scope="col">Smlouvy</th>
                    <th scope="col">Doplatky</th>
                    <th scope="col">Celkem vyplatit</th>
                    <th scope="col">Vr??tit klubu</th>
                    <th scope="col">Vyrovn??no</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($reportData as $key => $report) {
                    $row = '<tr data-emp-id=' . $key . ' class="table-' . ($report["resolved"] ? "success" : "danger") . '"><th scope="row">' . $report["name"] . '</th>';
                    $cashSum = 0;
                    $toPay = 0;
                    if (count($report["contracts"]) > 0) {
                        $row .= '<td><table class="table">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">N??zev</th>
                                <th scope="col">Odpracovan?? hodiny</th>
                                <th scope="col">Hodinov?? mzda</th>
                                <th scope="col">Mus?? b??t vyplaceo</th>
                                <th scope="col">Re??ln?? odpracovan?? hodiny</th>
                                <th scope="col">Re??ln?? vyplatit</th>
                                <th scope="col">Vyrovn??no</th>
                            </tr>
                        </thead>
                        <tbody>';
                        foreach ($report["contracts"] as $contKey => $contract) {
                            $toPayCont = round(($contract["minutes"] * ($contract["cashRate"] / 60)), 1);
                            $toPay += $toPayCont;
                            $row .= '<tr data-cont-id=' . $contKey . ' class="table-' . ($contract["resolved"] ? "success" : "danger") . '"><th scope="row">' . $contract["label"] . '</th>';
                            $row .= '<td>' . date('H:i', mktime(0, $contract["minutes"])) . ' / ' . $contract["maxHours"] . '</td>';
                            $row .= '<td>' . $contract["cashRate"] . ' K??/h</td>';
                            $row .= '<td>' . $toPayCont . ' K??</td>';
                            $row .= '<td>' . date('H:i', mktime(0, $contract["realHours"])) . '<a class="ms-2 btn btn-outline-primary rhBtn"><i class="bi bi-pencil"></i></a></td>';
                            $row .= '<td>' . $contract["realToPay"] . ' K??<a class="ms-2 btn btn-outline-primary rcBtn"><i class="bi bi-pencil"></i></a></td>';
                            $row .= '<td><div class="form-check form-switch"><input class="form-check-input contractSwitch" type="checkbox"' . ($contract["resolved"] ? " checked" : "") . '></div></td>';
                            $cashSum += $contract["realToPay"];
                        }
                        $row .= '</tbody></table></td>';
                    }
                    $fullSum = $cashSum + $report["additionalPayment"];
                    $row .= '<td>' . $report["additionalPayment"] . ' K?? <a class="ms-2 btn btn-outline-primary apBtn"><i class="bi bi-pencil"></i></a></td>';
                    $row .= '<td>' . $fullSum . '</td>';
                    $row .= '<td>' . ($toPay - $fullSum) . '</td>';
                    $row .= '<td><div class="form-check form-switch"><input class="form-check-input reportSwitch" type="checkbox"' . ($report["resolved"] ? "checked" : "") . '></div></td>';
                    echo $row . '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="realHoursFormModal" tabindex="-1" aria-labelledby="realHoursFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Re??ln?? odpracovan?? hodiny</h5>
                </div>
                <div class="modal-body">
                    <form class="needs-validation" novalidate action="writeRealHoursScript.php" method="post">
                        <div class="row">
                            <div class="col-4">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="rhContId" name="rhContId" readonly value="">
                                    <label for="rhContId">ID prac. vztahu</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="rhMonth" name="rhMonth" readonly value="<?php echo $_POST["month"]; ?>">
                                    <label for="rhMonth">M??s??c</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="rhYear" name="rhYear" readonly value="<?php echo $_POST["year"]; ?>">
                                    <label for="rhYear">Rok</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-floating mb-3 p-2">
                                    <input type="number" class="form-control" id="realHours" name="realHours" required value="">
                                    <label for="realHours">Hodiny</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-floating mb-3 p-2">
                                    <input type="number" class="form-control" id="realMinutes" name="realMinutes" required value="">
                                    <label for="realMinutes">Minuty</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">Ulo??it</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zav????t</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="realCashFormModal" tabindex="-1" aria-labelledby="realCashFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Re??ln?? vyplatit</h5>
                </div>
                <div class="modal-body">
                    <form class="needs-validation" novalidate action="writeRealCashScript.php" method="post">
                        <div class="row">
                            <div class="col-4">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="rcContId" name="rcContId" readonly value="">
                                    <label for="rcContId">ID prac. vztahu</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="rcMonth" name="rcMonth" readonly value="<?php echo $_POST["month"]; ?>">
                                    <label for="rcMonth">M??s??c</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="rcYear" name="rcYear" readonly value="<?php echo $_POST["year"]; ?>">
                                    <label for="rcYear">Rok</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating mb-3 p-2">
                                    <input type="number" class="form-control" id="realCash" name="realCash" required value="">
                                    <label for="realCash">Vyplatit</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">Ulo??it</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zav????t</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="additionalPaymentFormModal" tabindex="-1" aria-labelledby="additionalPaymentFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Doplatky</h5>
                </div>
                <div class="modal-body">
                    <form class="needs-validation" novalidate action="writeAdditionalPaymentScript.php" method="post">
                        <div class="row">
                            <div class="col-4">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="apEmpId" name="apEmpId" readonly value="">
                                    <label for="apEmpId">ID zam.</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="apMonth" name="apMonth" readonly value="<?php echo $_POST["month"]; ?>">
                                    <label for="apMonth">M??s??c</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-floating mb-3 p-2">
                                    <input type="text" class="form-control" id="apYear" name="apYear" readonly value="<?php echo $_POST["year"]; ?>">
                                    <label for="apYear">Rok</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating mb-3 p-2">
                                    <input type="number" class="form-control" id="additionalPayment" name="additionalPayment" required value="">
                                    <label for="additionalPayment">Doplatky</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">Ulo??it</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zav????t</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="changeReportStatusResponseModal" tabindex="-1" aria-labelledby="changeReportStatusResponseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Odpov???? ze serveru</h5>
                </div>
                <div class="modal-body">
                    <p id="changeReportStatusResponseText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Zav????t</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $(".rhBtn").click(function() {
                $("#rhContId").val($(this).parent().parent().data("contId"));
                $('#realHoursFormModal').modal('show');
            });

            $(".rcBtn").click(function() {
                $("#rcContId").val($(this).parent().parent().data("contId"));
                $('#realCashFormModal').modal('show');
            });

            $(".apBtn").click(function() {
                $("#apEmpId").val($(this).parent().parent().data("empId"));
                $('#additionalPaymentFormModal').modal('show');
            });

            $(".reportSwitch, .contractSwitch").click(function() {
                $(this).parent().parent().parent().attr("class", ($(this).is(":checked") ? "table-success" : "table-danger"));

                var contract = $(this).hasClass("reportSwitch"); 
                $.post("changeReportStatus.php", {
                    contract: !contract,
                    month: $("#mainTable").data("month"),
                    year: $("#mainTable").data("year"),
                    id: $(this).parent().parent().parent().data((contract ? "emp" : "cont") + "Id"),
                    status: $(this).is(":checked")
                }, function(data) {
                    $("#changeReportStatusResponseText").text(data);
                    $("#changeReportStatusResponseModal").modal("show");
                });
            });
        });
    </script>
</body>

</html>