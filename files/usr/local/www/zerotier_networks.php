<?php
require_once("guiconfig.inc");
require_once("zerotier.inc");

$pgtitle = array(gettext("VPN"), gettext("Zerotier"), gettext("Networks"));
$pglinks = array("", "pkg_edit.php?xml=zerotier.xml", "@self");
require("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Networks"), true, "zerotier_networks.php");
$tab_array[] = array(gettext("Peers"), false, "zerotier_peers.php");
$tab_array[] = array(gettext("Controller"), false, "zerotier_controller.php");
$tab_array[] = array(gettext("Configuration"), false, "zerotier.php");
add_package_tabs("Zerotier", $tab_array);
display_top_tabs($tab_array);

if (!is_service_running("zerotier")) {
    print_info_box(gettext("The Zerotier service is not running."), "warning", false);
}
if (isset($_REQUEST['act'])) {
    $act = $_REQUEST['act'];
}
if ($_POST['save']) {
    $out = zerotier_join_network($_POST['Network']);
    header("Location: zerotier_networks.php");
    exit;
}
if ($act=="del") {
    $out = zerotier_leave_network($_POST['Network']);
    header("Location: zerotier_networks.php");
    exit;
}
if ($_POST['Update']) {
    $out = zerotier_leave_network($_POST['NetworkOriginal']);
    $out = zerotier_join_network($_POST['Network']);
    header("Location: zerotier_networks.php");
}
if ($act=="new" || $act=="edit"):
    $network = $_REQUEST['Network'];

    if($act=="new") {
        $form = New Form();
    }
    else {
        $form = New Form(false);
    }

    $section = new Form_Section('Join Network');
    $section->addInput(new Form_Input(
        'Network',
        'Network',
        'text',
        $network,
        ['min' => '0']
    ))->setHelp("A 16 digit ID.");
    if ($act=="edit") {
        $form ->addGlobal(new Form_Input(
            'NetworkOriginal',
            'Network',
            'hidden',
            $network,
            ['min' => '0']
        ));
        $btnUpdate = new Form_Button(
            'Update',    // name
            'Update',    // Label text
            NULL,
            'fa-save'
        );
        $btnUpdate->removeClass('btn-default')->addClass('btn-warning');
        $form->addGlobal($btnUpdate);
    }
    $form->add($section);
    print($form);
else:
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">Zerotier Networks</h2>
    </div>
    <div class="table-responsive panel-body">
        <table class="table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th><?=gettext("Status")?></th>
                    <th><?=gettext("Network")?></th>
                    <th><?=gettext("Type")?></th>
                    <th><?=gettext("Addresses")?></th>
                    <th><?=gettext("Bridged")?></th>
                    <th><?=gettext("Actions")?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $networks = zerotier_listnetworks();
                    foreach($networks as $network) {
                ?>
                    <tr>
                        <td>
                            <span class="label label-<?php print(get_status_class($network->status)); ?>"><?php print($network->status); ?></span>
                        </td>
                        <td><?php print($network->id); print("<br />"); print("<strong>".$network->name."</strong>"); ?></td>
                        <td><?php print($network->type); ?></td>
                        <td><?php print(implode('<br/>',array_reverse($network->assignedAddresses))); ?></td>
                        <td><?php print($network->bridge ? "Yes" : "No"); ?></td>
                        <td>
                            <a href="?act=edit&amp;Network=<?=$network->id;?>" class="fa fa-pencil" title="<?=gettext('Edit Network')?>"></a>
                            <a href="?act=del&amp;Network=<?=$network->id;?>" class="fa fa-trash" title="<?=gettext('Leave Network')?>" usepost></a>
                        </td>
                    </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>
<nav class="action-buttons">
    <a href="zerotier_networks.php?act=new" class="btn btn-sm btn-success btn-sm">
        <i class="fa fa-plus icon-embed-btn"></i> Join
    </a>
</nav>
<?php
    endif;
    include("foot.inc");
 ?>
