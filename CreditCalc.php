<?php // login.php
    $hn = 'localhost';
    $db = 'Calculator';
    $un = 'root';
    $pw = '';
    $conn = new mysqli($hn, $un, $pw, $db);
    //получаем значения с формы
    $summa      = get_post($conn, "summa");
    $valuta     = get_post($conn, "valuta");            //1-руб, 2-$, 3-евро
    $period     = get_post($conn, "period");
    $permesgod  = get_post($conn, "permesgod");         //1-мес, 12-год
    $stavka     = get_post($conn, "stavka");
    $stavmesgod = get_post($conn, "stavmesgod");        //12-в год, 1-в мес
    $platezh_vid = get_post($conn, "platezh_vid");      //1-аннуитетный, 2-дифференцированный
    $messtart   = get_post($conn, "messtart");          //начало выплат по месяцу
    $godstart = get_post($conn, "godstart");            //начало выплат по году
    
    $stavka = $stavka / $stavmesgod;
    $rows = $period * $permesgod;                       //кол-во платежей(период)
    $OD   = round($summa / ($period * $permesgod), 2);  //основной долг

    //парсим сайт для полкчения значений валюты
    require_once 'kurs_valut.php';
    switch($valuta){
        case 1:
            $summa = $summa * 1;
            break;
        case 2:
            $summa = $summa * $dollar;
            break;
        case 3:
            $summa = $summa * $euro;
            break;
    }
    //переменные для суммы полей
    $sumOK = 0;
    $sumNP = 0;
    $sumSP = 0;
    $sumOD = 0;
    end_table('<b>Номер платежа</b>', '<b>Задолженность по кредиту</b>', '<b>Начисленные проценты</b>', '<b>Основной долг</b>', '<b>Сумма платежа</b>');
    delete_table_info($conn);
    
    for($i = 1; $i <= $rows; $i++)
    {
        switch($platezh_vid){
            case 1:
                    $PS = $stavka/100;                                     //процентная ставка
                    $SP = $summa * ($PS + ($PS / ((pow(1 + $PS, $rows)) - 1))); //сумма платежа в месяц
                    $SP = round($SP, 2);
                    if ($i == 1){
                        $OK = $summa;
                    }else{
                        $OK = $OK - $OD;
                    }
                    $NP = round(($OK) * $PS, 2);
                    $OD = round($SP - $NP, 2);
                    //insert_table_info($conn, $i, $OK, $NP, $OD, $SP);
                    insert_table_info($conn, russian_date($messtart, $godstart), $OK, $NP, $OD, $SP);
                    $messtart++;
                    //общая сумма полей
                    $sumOK = $sumOK + $OK;
                    $sumNP = $sumNP + $NP; 
                    $sumSP = $sumSP + $SP; 
                    $sumOD = $sumOD + $OD; 
                break;
            case 2:
                    $OK = round($summa - ($OD * ($i - 1)), 2);      //задолжность по кредиту
                    $NP = round($OK * ($stavka/100), 2);         //начисленные проценты
                    $SP = round($NP + $OD, 2);                      //сумма платежа
                    insert_table_info($conn, russian_date($messtart, $godstart), $OK, $NP, $OD, $SP);
                    $messtart++;
                    //общая сумма полей
                    $sumOK = $sumOK + $OK;
                    $sumNP = $sumNP + $NP; 
                    $sumSP = $sumSP + $SP; 
                    $sumOD = $sumOD + $OD; 
                break;
            default:
                echo "Сбой с видом платежа";
        }
        if($i == $rows){
            end_table('<b>Итог</b>', $sumOK, $sumNP, $sumOD, $sumSP);
        }
    }
    
    //добавляем данные в таблицу
    function insert_table_info($conn, $i, $OK, $NP, $OD, $SP)
    {
        $query = "INSERT INTO CreditCalculator VALUES"."('$i', '$OK', '$NP', '$OD', '$SP')";
                $result = $conn->query($query);
                if (!$result) echo "Сбой при вставке данных: $query<br>" .
                $conn->error . "<br><br>";
        end_table($i, $OK, $NP, $OD, $SP);
    }
    
    //вывод данных из формы
    function get_post($conn, $var)
    {
        return $conn->real_escape_string($_POST[$var]);
    }
    
    //отчистка таблицы
    function delete_table_info($conn)
    {
        $query = "TRUNCATE TABLE CreditCalculator";
        $result = $conn->query($query);
        if (!$result) echo "Сбой при удалении данных: $query<br>" .
        $conn->error . "<br><br>";
    }

    //заполняем таблицу полученными данными
    function end_table($i, $OK, $NP, $OD, $SP)
    {
echo <<<_END
    <table width="993" border="collapse" align="center" cellpadding="0" cellspacing="0" font="17px arial bold" bgcolor="#b0e0e6">
    <tr>
        <td width="150" align="center">$i</td>
        <td width="150" align="center">$OK</td>
        <td width="150" align="center">$NP</td>
        <td width="150" align="center">$OD</td>
        <td width="150" align="center">$SP</td>
    </tr>
    </table>
_END;
    }

    //ф-ция для записи и отображения даты
    function russian_date($mounth, $year){
        for ($i = 0; $i < 100; $i++){
            if ($mounth > 12){
                $mounth = $mounth - 12;
                $year++;
            }
        }
        switch ($mounth){
            case 1:  $m='January'; break;
            case 2:  $m='February'; break;
            case 3:  $m='March'; break;
            case 4:  $m='April'; break;
            case 5:  $m='May'; break;
            case 6:  $m='June'; break;
            case 7:  $m='July'; break;
            case 8:  $m='August'; break;
            case 9:  $m='September'; break;
            case 10: $m='October'; break;
            case 11: $m='November'; break;
            case 12: $m='December'; break;
        }
        return $m.' '.$year;
    }
?>