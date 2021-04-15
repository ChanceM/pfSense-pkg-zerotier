<?php
require_once("config.inc");
require_once("guiconfig.inc");
require_once("zerotier.inc");

function get_enabled_icon($value) {
    $icon = "";

    switch($value) {
        case TRUE:
            $icon = 'check text-success';
            break;
        case FALSE:
            $icon = 'times text-danger';
            break;
    }
    return $icon;
}

function translate_v4AssignMode($index) {
    $modes = ['zt'];

    return $modes[$index];
}

function translate_v6AssignMode($index) {
    $modes = ['zt','6plane','rfc4193'];
    return $modes[$index];
}

$pgtitle = array(gettext("VPN"), gettext("Zerotier"), gettext("Controller"), $_REQUEST['Network']);
$pglinks = array("", "pkg_edit.php?xml=zerotier.xml", 'zerotier_controller.php', "@self");

if (isset($_REQUEST['act'])) {
    $act = $_REQUEST['act'];
}

require("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Networks"), false, "zerotier_networks.php");
$tab_array[] = array(gettext("Peers"), false, "zerotier_peers.php");
$tab_array[] = array(gettext("Controller"), false, "zerotier_controller.php");
$tab_array[] = array(gettext("Configuration"), false, "zerotier.php");
add_package_tabs("Zerotier", $tab_array);
display_top_tabs($tab_array);

if (isset($_REQUEST['Network'])) {
    $networkID = $_REQUEST['Network'];
}

if (!is_service_running("zerotier")) {
    print_info_box(gettext("The Zerotier service is not running."), "warning", false);
}
if ($act == "toggle") {
    $member = $_REQUEST['member'];
    $value = $_REQUEST['value'];
    $network = $_REQUEST['Network'];

    switch($value) {
        case "authorize":
            zerotier_controller_member_toggle($network, $member, 'authorized');
            break;
        case "bridge":
            zerotier_controller_member_toggle($network, $member, 'activeBridge');
            break;
    }
    header("Location: zerotier_controller_network.php?Network=${networkID}");
    exit;
}

function get_member_index($memberID, $members) {
    
    $memberList = array_column($members, 'alias', 'id');
    return array_search($memberID, array_keys($memberList));
}

if ($act=="del") {
    $out = zerotier_controller_deletenetwork($_POST['Network']);
    header("Location: zerotier_controller.php");
    exit;
}
if ($_POST['save']) {
    global $config;
    global $id;

    $networkID = $_POST['Network'];
    $memberID = $_POST['Member'];
    $alias = $_POST['Alias'];
    $members = $config['installedpackages']['zerotiercontroller']['config'][0]['member'];
    $index = get_member_index($memberID, $members) === TRUE ? get_member_index($memberID, $members) : FALSE;
    
    if($alias != '' && $index === FALSE) {
         $config['installedpackages']['zerotiercontroller']['config'][0]['member'][$id] = ['id'=> $memberID, 'alias' => $alias ];
    }
    else if ($alias != '' && $index !== False ) {
        $config['installedpackages']['zerotiercontroller']['config'][0]['member'][$index] = ['id'=> $memberID, 'alias' => $alias ];
    }
    else {
        if(is_array($config['installedpackages']['zerotiercontroller']['config'][0]['member']))
        {
            unset($config['installedpackages']['zerotiercontroller']['config'][0]['member'][$index]);
        }
    }
    write_config();
    
    header("Location: zerotier_controller_network.php?Network=${networkID}");
    exit;
}
if ($act=="new" || $act=="edit"):
    $memberID = $_POST['member'];
    $form = new Form();
    $section = new Form_Section('Name Member');
    $section->addInput(new Form_Input(
        'Alias',
        'Alias',
        'text',
        NULL,
        ['min' => '0']
    ))->setHelp("A short name to identify the member.");
    if ($act=="edit") {
        $form ->addGlobal(new Form_Input(
            'Network',
            'Network',
            'hidden',
            $networkID,
            ['min' => '0']
        ));
        $form ->addGlobal(new Form_Input(
            'Member',
            'member',
            'hidden',
            $memberID,
            ['min' => '0']
        ));
    }
    $form->add($section);
    print($form);
else:
    $network = zerotier_controller_network($networkID);
    // print($networkID);
    // [TODO] Finish output of all pertinent data about network
    // print_r($network);
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Network: <?php print($network->id); ?></h2></div>
	<div class="panel-body">
		<dl class="dl-horizontal">
        <dt><?php print(gettext("Name")); ?><dt><dd><?php print($network->name) ?></dd>
        <dt><?php print(gettext("Type")); ?><dt><dd><?php print($network->private) == TRUE ? 'Private' : 'Public' ?></dd>
        <dt><?php print(gettext("Broadcast Enabled")); ?><dt><dd><?php print($network->enableBroadcast) ? 'Yes' : 'No' ?></dd>
        <dt><?php print(gettext("Multicast Limit")); ?><dt><dd><?php print($network->multicastLimit) ?></dd>
        <dt><?php print(gettext("IP Pools")); ?><dt><dd>
        <?php foreach($network->ipAssignmentPools as $pool) {?>
            <?php print($pool->ipRangeStart) ?> &mdash; <?php print($pool->ipRangeEnd) ?> <br>
        <?php
            }
        ?>
        </dd>
        </dl>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">Networks Members</h2>
    </div>
    <div class="table-responsive panel-body">
        <table class="table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Address</th>
                    <th>Authorized</th>
                    <th>Bridged</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $members = zerotier_controller_network_members($_REQUEST['Network']);
                    foreach($members as $member) {
                ?>
                    <tr>
                        <td><?php print($member->id);
                                // print(
                                //     array_column([array_search($network->id,array_column($config['installedpackages']['zerotier']['config'][0]['networks'], 'networkID'), FALSE)]['members'], 'alias', 'memberID')[$member->id]
                                // );
                                // print_r($config['installedpackages']['zerotier']['config'][0]['networks']);
                                print('<br>');
                                print_r(array_column($config['installedpackages']['zerotiercontroller']['config'][0]['member'], 'alias', 'id')[''.$member->id][0]);
                            ?>
                        </td>
                        <td><?php print(implode('<br/>', $member->ipAssignments)); ?></td>
                        <td><?php
                            print("<a href=\"?act=toggle&Network=$network->id&member=$member->id&value=authorize\" usepost title=\"Click to toggle Authorization\">");?>
                            <i class="fa fa-<?php print(get_enabled_icon($member->authorized));?>"  style="cursor: pointer;"></i>
                            <?php print("</a>")?>
                        </td>
                        <td><?php print("<a href=\"?act=toggle&Network=$network->id&member=$member->id&value=bridge\" usepost title=\"Click to toggle Bridging\">");?>
                            <i class="fa fa-<?php print(get_enabled_icon($member->activeBridge));?>" style="cursor: pointer;"></i>
                            <?php print("</a>")?>
                        </td>
                        <td>
                            <a href="?act=edit&amp;Network=<?=$network->id;?>&amp;member=<?=$member->id;?>" class="fa fa-pencil" title="<?=gettext('Name Member')?>" usepost></a>
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
    <form action="zerotier_controller.php?act=del" method="post">
        <input type="hidden" name="Network" id="Network" value="<?=$network->id?>">
        <button type="submit" title="<?=gettext('Remove Network')?>" class="confirm btn btn-sm btn-danger">
            <i class="fa fa-trash icon-embed-btn"></i> Delete
        </button>
    </form>
</nav>
<?php
endif;
include("foot.inc"); ?>
