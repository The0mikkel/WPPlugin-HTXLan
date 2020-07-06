<?php
    // Tabel med alle tilmeldinger som kan ses - Evt en knap som kan trykkes, hvor så at felterne kan blive redigerbare - Man kan vælge imellem forskellige forms
    // Widgets and style
    HTX_load_standard_backend();

    // Getting start information for database connection
    global $wpdb;
    // Connecting to database, with custom variable
    $link = database_connection();

    // Header
    echo "<h1>HTX Lan tilmeldinger</h1>";

    // Getting data about forms
    $table_name = $wpdb->prefix . 'htx_form_tables';
    $stmt = $link->prepare("SELECT * FROM `$table_name` where active = 1 ORDER BY favorit DESC, tableName ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows === 0) echo "Ingen registreringer"; else {
        while($row = $result->fetch_assoc()) {
            $tableIds[] = $row['id'];
            $tableNames[] = $row['tableName'];
        }

        // Getting table id
        if (in_array(intval($_GET['formular']), $tableIds)) $tableId = intval($_GET['formular']); else $tableId = $tableIds[0];

        // Post handling
        participantList_post($tableId);

        // Dropdown menu
        // Starting dropdown menu
        echo "<p><h3>Formular:</h3> ";
        echo "<form method=\"get\"><input type='hidden' name='page' value='HTX_lan_participants_list'><select name='formular' class='dropdown' onchange='form.submit()'>";
        // writing every option
        for ($i=0; $i < count($tableIds); $i++) { 
            // Seeing if value is the choosen one
            if ($tableIds[$i] == $tableId) $isSelected = "selected"; else $isSelected = "";

            // Writing value
            echo "<option value='$tableIds[$i]' $isSelected>$tableNames[$i]</option>";
        }
        
        // Ending dropdown
        echo "</select></form><br></p>";
        
        // Start of table with head
        echo "<div class='formGroup formGroup_scroll_left'><div class='formGroup_container'><table class='InfoTable'><thead><tr>";

        // Getting information from database
        // Users
        $table_name = $wpdb->prefix . 'htx_form_users';
        $stmt = $link->prepare("SELECT * FROM `$table_name` WHERE tableid = ? AND active = 1");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows === 0) echo "Ingen registreringer"; else {
            // Getting every column
            $table_name3 = $wpdb->prefix . 'htx_column';
            $stmt3 = $link->prepare("SELECT * FROM `$table_name3` WHERE tableid = ?");
            $stmt3->bind_param("i", $tableId);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            if($result3->num_rows === 0) {return HTX_frontend_sql_notworking();} else {
                while($row3 = $result3->fetch_assoc()) {
                    $columnNameFront[] = $row3['columnNameFront'];
                    $columnNameBack[] = $row3['columnNameBack'];
                    $format[] = $row3['format'];
                    $columnType[] = $row3['columnType'];
                    $special[] = $row3['special'];
                    $specialName[] = $row3['specialName'];
                    $placeholderText[] = $row3['placeholderText'];
                    $sorting[] = $row3['sorting'];
                    $required[] = $row3['required'];
                }
            }
            $stmt3->close();

            // Writing every column and insert into table head
            for ($i=0; $i < count($columnNameBack); $i++) { 
                echo "<th>$columnNameFront[$i]</th>";
            }
            // Writing extra lines
            echo "<th>Betaling</th>";
            // Ending head
            echo "</tr></head>";

            // User information
            // Stating table body
            echo "<tbody>";

            // Getting every user ids
            while($row = $result->fetch_assoc()) {
                $userid[] = $row['id'];
                $payed[] = $row['payed'];
            }
            // Getting and writing every user information
            for ($i=0; $i < count($userid); $i++) { 
                echo "<tr>";
                // For every column
                for ($index=0; $index < count($columnNameBack); $index++) { 
                    echo "<td class=''>";
                    // Getting data for specefied column
                    $table_name2 = $wpdb->prefix . 'htx_form';
                    $stmt2 = $link->prepare("SELECT * FROM `$table_name2` WHERE tableid = ? AND userId = ? AND name = ?");
                    $stmt2->bind_param("iis", $tableId, $userid[$i], $columnNameBack[$index]);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if($result2->num_rows === 0) echo "<i style='color: red'>Null</i>"; else {
                        while($row2 = $result2->fetch_assoc()) {
                            // Writing data from table
                            echo $row2['value'];
                        } 
                    }
                    $stmt2->close();
                    echo "</td>";
                }
                // Adding payed, and arrived at the end of inputs
                // Payed

                // Getting different payed option - These are pre determined, such as cash, mobilepay, bank, free ticket, free
                $paymentMethods = array("Kontant", "Mobilepay");
                $paymentMethodsId = array("0", "0-f", "1-f","0-i", "1-i");

                echo "<td ";
                if ($payed[$i] == "0") echo "class='unpayed'"; else if ($payed[$i] == "0-i" OR $payed[$i] == "1-i") echo "class='crewpayed'"; else if (in_array($payed[$i], $paymentMethodsId)) echo "class='payed'"; 
                echo ">
                    <form id='$i-pay' method='POST'>
                    <input type='hidden' name='userId' value='$userid[$i]'>
                    <input type='hidden' name='post' value='paymentUpdate'>
                    <select name='paymentOption'  onchange='document.getElementById(\"$i-pay\").submit()'>
                        <option value='0'";
                    if ($payed[$i] == 0) echo "selected";
                        echo">Ingen</option>
                        <optgroup label='Fuld pris'>";
                for ($j=0; $j < count($paymentMethods); $j++) { 
                    echo "<option value='$j-f'";
                    if ($payed[$i] == "$j-f") echo "selected";
                    echo">$paymentMethods[$j]</option>";
                }
                echo "</optgroup><optgroup label='Gratis indgang'>";
                for ($j=0; $j < count($paymentMethods); $j++) { 
                    echo "<option value='$j-i'";
                    if ($payed[$i] == "$j-i") echo "selected";
                    echo">$paymentMethods[$j]</option>";
                }
                echo "</optgroup>
                    </select>
                    </form>
                </td>";

                // Arrived


            }
            
        }
        $stmt->close();

        // Ending table
        echo "</thead></table></div>";
    }
?>