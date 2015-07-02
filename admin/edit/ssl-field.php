<?php
/**
 * /admin/edit/ssl-field.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (C) 2010-2015 Greg Chetcuti <greg@chetcuti.com>
 *
 * Project: http://domainmod.org   Author: http://chetcuti.com
 *
 * DomainMOD is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with DomainMOD. If not, see
 * http://www.gnu.org/licenses/.
 *
 */
?>
<?php
include("../../_includes/start-session.inc.php");
include("../../_includes/init.inc.php");

require_once(DIR_ROOT . "classes/Autoloader.php");
spl_autoload_register('DomainMOD\Autoloader::classAutoloader');

$error = new DomainMOD\Error();
$system = new DomainMOD\System();
$time = new DomainMOD\Timestamp();

include(DIR_INC . "head.inc.php");
include(DIR_INC . "config.inc.php");
include(DIR_INC . "software.inc.php");
include(DIR_INC . "database.inc.php");

$system->authCheck();
$system->checkAdminUser($_SESSION['is_admin'], $web_root);

$page_title = "Editing A Custom SSL Field";
$software_section = "admin-ssl-field-edit";

$del = $_GET['del'];
$really_del = $_GET['really_del'];

$csfid = $_GET['csfid'];

$new_name = $_POST['new_name'];
$new_description = $_POST['new_description'];
$new_csfid = $_POST['new_csfid'];
$new_notes = $_POST['new_notes'];

if ($new_csfid == "") $new_csfid = $csfid;

$sql = "SELECT id
        FROM ssl_cert_fields
        WHERE id = '" . $csfid . "'";
$result = mysqli_query($connection, $sql);

if (mysqli_num_rows($result) == 0) {

    $_SESSION['result_message'] .= "You're trying to edit an invalid Custom SSL Field<BR>";

    header("Location: ../ssl-fields.php");
    exit;

}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $new_name != "") {

    $sql = "UPDATE ssl_cert_fields
            SET name = '" . mysqli_real_escape_string($connection, $new_name) . "',
                description = '" . mysqli_real_escape_string($connection, $new_description) . "',
                notes = '" . mysqli_real_escape_string($connection, $new_notes) . "',
                update_time = '" . $time->time() . "'
            WHERE id = '" . $new_csfid . "'";
    $result = mysqli_query($connection, $sql) or $error->outputOldSqlError($connection);

    $sql = "SELECT field_name
            FROM ssl_cert_fields
            WHERE id = '" . $new_csfid . "'";
    $result = mysqli_query($connection, $sql) or $error->outputOldSqlError($connection);
    while ($row = mysqli_fetch_object($result)) {
        $temp_field_name = $row->field_name;
    }

    $_SESSION['result_message'] .= "Custom SSL Field <div class=\"highlight\">" . $new_name . " (" . $temp_field_name . ")</div> Updated<BR>";

    header("Location: ../ssl-fields.php");
    exit;

} else {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        if ($new_name == "") $_SESSION['result_message'] .= "Enter the display name<BR>";

    } else {

        $sql = "SELECT f.name, f.field_name, f.description, f.notes, f.insert_time, f.update_time, t.name AS type
                FROM ssl_cert_fields AS f, custom_field_types AS t
                WHERE f.type_id = t.id
                  AND f.id = '" . $csfid . "'
                ORDER BY f.name";
        $result = mysqli_query($connection, $sql) or $error->outputOldSqlError($connection);

        while ($row = mysqli_fetch_object($result)) {

            $new_name = $row->name;
            $new_field_name = $row->field_name;
            $new_description = $row->description;
            $new_field_type = $row->type;
            $new_notes = $row->notes;

        }

    }

}

if ($del == "1") {

    $_SESSION['result_message'] = "Are you sure you want to delete this Custom SSL Field?<BR><BR><a href=\"ssl-field.php?csfid=" . $csfid . "&really_del=1\">YES, REALLY DELETE THIS CUSTOM SSL FIELD</a><BR>";

}

if ($really_del == "1") {

    if ($csfid == "") {

        $_SESSION['result_message'] = "The Custom SSL Field cannot be deleted<BR>";

    } else {

        $sql = "SELECT name, field_name
                FROM ssl_cert_fields
                WHERE id = '" . $csfid . "'";
        $result = mysqli_query($connection, $sql);
        while ($row = mysqli_fetch_object($result)) {
            $temp_name = $row->name;
            $temp_field_name = $row->field_name;
        }

        $sql = "ALTER TABLE `ssl_cert_field_data`
                DROP `" . $temp_field_name . "`";
        $result = mysqli_query($connection, $sql);

        $sql = "DELETE FROM ssl_cert_fields
                WHERE id = '" . $csfid . "'";
        $result = mysqli_query($connection, $sql);

        $_SESSION['result_message'] = "Custom SSL Field <div class=\"highlight\">" . $temp_name . " (" . $temp_field_name . ")</div> Deleted<BR>";

        header("Location: ../ssl-fields.php");
        exit;

    }

}
?>
<?php include(DIR_INC . 'doctype.inc.php'); ?>
<html>
<head>
    <title><?php echo $system->pageTitle($software_title, $page_title); ?></title>
    <?php include(DIR_INC . "layout/head-tags.inc.php"); ?>
</head>
<body>
<?php include(DIR_INC . "layout/header.inc.php"); ?>
<form name="edit_user_form" method="post">
    <strong>Display Name (75)</strong><a title="Required Field">
        <div
            class="default_highlight">*
        </div>
    </a><BR><BR><input name="new_name" type="text" size="30" maxlength="75"
                       value="<?php if ($new_name != "") echo htmlentities($new_name); ?>"><BR><BR>
    <strong>Database Field Name</strong><BR><BR><?php echo $new_field_name; ?><BR><BR>
    <strong>Data Type</strong><BR><BR>
    <?php echo $new_field_type; ?>
    <BR><BR>
    <strong>Description (255)</strong><BR><BR><input name="new_description" type="text" size="50" maxlength="255"
                                                     value="<?php if ($new_description != "") echo htmlentities($new_description); ?>">
    <BR><BR>
    <strong>Notes</strong><BR><BR>
    <textarea name="new_notes" cols="60" rows="5"><?php echo $new_notes; ?></textarea>
    <input type="hidden" name="new_csfid" value="<?php echo $csfid; ?>">
    <BR><BR>
    <input type="submit" name="button" value="Update Custom Field &raquo;">
</form>
<BR><BR><a href="ssl-field.php?csfid=<?php echo $csfid; ?>&del=1">DELETE THIS CUSTOM SSL FIELD</a>
<?php include(DIR_INC . "layout/footer.inc.php"); ?>
</body>
</html>