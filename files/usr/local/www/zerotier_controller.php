<?php
require_once("config.inc");
require_once("guiconfig.inc");
require_once("zerotier.inc");

function translate_v4AssignMode($index) {
    $modes = ['zt'];

    return $modes[$index];
}

function translate_v6AssignMode($index) {
    $modes = ['zt','6plane','rfc4193'];
    return $modes[$index];
}

$pgtitle = array(gettext("VPN"), gettext("Zerotier"), gettext("Controller"));
$pglinks = array("", "pkg_edit.php?xml=zerotier.xml", "@self");

if (isset($_REQUEST['act'])) {
    $act = $_REQUEST['act'];
}

require("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Networks"), false, "zerotier_networks.php");
$tab_array[] = array(gettext("Peers"), false, "zerotier_peers.php");
$tab_array[] = array(gettext("Controller"), true, "zerotier_controller.php");
$tab_array[] = array(gettext("Configuration"), false, "zerotier.php");
add_package_tabs("Zerotier", $tab_array);
display_top_tabs($tab_array);


if (!is_service_running("zerotier")) {
    print_info_box(gettext("The Zerotier service is not running."), "warning", false);
}
if ($act=="del") {
    $out = zerotier_controller_deletenetwork($_POST['Network']);
    header("Location: zerotier_controller.php");
    exit;
}
if ($_POST['save']) {
    $id = $_POST['NetworkID'] != '' ? $_POST['NetworkID'] : '______';
    $zerotier_network = [];
    $zerotier_network['name'] = $_POST['Name'];
    $zerotier_network['private'] = $_POST['private'] == 'yes' ? true : false;
    $zerotier_network['enableBroadcast'] = $_POST['enableBroadcast'] == 'yes' ? true : false;
    $zerotier_network['v4AssignMode'] = translate_v4AssignMode($_POST['v4AssignMode'][0]);
    foreach ($_POST['v6AssignMode'] as $v6mode) {
        $zerotier_network['v6AssignMode'][] = [translate_v6AssignMode($v6mode) => TRUE];
    }
    $zerotier_network['multicastLimit'] = (integer) $_POST['multicastLimit'];
    $zerotier_network['routes'] = [];
    $zerotier_network['rules'] = [];
    $zerotier_network['ipAssignmentPools'] = [];
    $zerotier_network['allowPassiveBridging'] = false;

    if (!isset($_POST['allowAnyProtocol'])) {
        if (isset($_POST['AllowIPv4'])) {
            $zerotier_network['rules'][] = (object)['etherType' => 2048, 'not' => true, 'or' => false, 'type' => 'MATCH_ETHERTYPE'];
            $zerotier_network['rules'][] = (object)['etherType' => 2054, 'not' => true, 'or' => false, 'type' => 'MATCH_ETHERTYPE'];
            $zerotier_network['rules'][] = (object)[ 'type' => 'ACTION_ACCEPT'];
        }
        if (isset($_POST['AllowIPv6'])) {
            $zerotier_network['rules'][] = (object)['etherType' => 34525, 'not' => true, 'or' => false, 'type' => 'MATCH_ETHERTYPE'];
            $zerotier_network['rules'][] = (object)[ 'type' => 'ACTION_ACCEPT'];
        }
    }
    else {
        $zerotier_network['rules'][] = (object)[ 'type' => 'ACTION_ACCEPT'];
    }

    if ($zerotier_network['v4AssignMode'] == 'zt') {
        $zerotier_network['ipAssignmentPools'][] = ['ipRangeStart' => $_POST['ipRangeStartv4'], 'ipRangeEnd' => $_POST['ipRangeEndv4']];
    }
    if ($zerotier_network['v6AssignMode'] == 'zt') {
        $zerotier_network['ipAssignmentPools'][] = ['ipRangeStart' => $_POST['ipRangeStartv6'], 'ipRangeEnd' => $_POST['ipRangeEndv6']];
    }

    foreach (explode(',',$_POST['Routes']) as $route) {
        $zerotier_network['routes'][] = ['target' => $route, 'via'=> NULL];
    }

    if ($config['installedpackages']['zerotier']['config'][0]['experimental']) {
        $zerotier_network['allowPassiveBridging'] = $_POST['allowPassiveBridging'] == 'on' ? TRUE : FALSE;
    }

    $out = zerotier_controller_createnetwork($zerotier_network, $id);
    
    header("Location: zerotier_controller.php");
    exit;
}
if ($act=="new" || $act=="edit"):
    $zerotier_network = [];
    $zerotier_network["private"] = TRUE;
    $zerotier_network["enableBroadcast"] = TRUE;
    $zerotier_network['v4AssignMode'] = 0;
    $zerotier_network['v6AssignMode'] = 1;
    $zerotier_network["AllowIPv4"] = 1;
    $zerotier_network['multicastLimit'] = 32;

    $form = new Form();
    $section = new Form_Section('Create Network');
    $section->addInput(new Form_Input(
        'NetworkID',
        'Network ID',
        'text',
        $zerotier_network['NetworkID'],
        ['min' => '0']
    ))->setHelp("A 6 digit ID that is appended to the server address. Leave blank to use a random ID.");
    $section->addInput(new Form_Input(
        'Name',
        '*Name',
        'text',
        $zerotier_network['name'],
        ['min' => '0']
    ))->setHelp("A short name for this network.");
    $section->addInput(new Form_Checkbox(
        'private',
        '*Private',
        'Enable access control on this network.',
        $zerotier_network["private"]
    ))->setHelp('Set this option to enable access control.');
    $section->addInput(new Form_Checkbox(
        'enableBroadcast',
        '*Enable Broadcast',
        'Ethernet ff:ff:ff:ff:ff:ff allowed?',
        $zerotier_network["private"]
    ))->setHelp('Set this option to enable Ethernet Broadcast (ff:ff:ff:ff:ff:ff allowed?)');
    $section->addInput(new Form_Select(
        'v4AssignMode',
        '*IPv4 Assign Mode',
        $zerotier_network['v4AssignMode'],
        ['zt'],
        TRUE
    ))->addClass('multiselect');

    $group = new Form_Group('*Range');

    $group->add(new Form_IpAddress(
        'ipRangeStartv4',
        null,
        $zerotier_network['ipAssignmentPools']['ipRangeStart'],
        'V4'
    ))->setHelp('From');

    $group->add(new Form_IpAddress(
        'ipRangeEndv4',
        null,
        $zerotier_network['ipAssignmentPools']['ipRangeEnd'],
        'V4'
    ))->setHelp('To');

    $section->add($group);
    $section->addInput(new Form_Select(
        'v6AssignMode',
        '*IPv6 Assign Mode',
        $zerotier_network['v6AssignMode'],
        ['zt','6plane','rfc4193'],
        TRUE
    ))->addClass('multiselect');
        $group = new Form_Group('Range');

    $group->add(new Form_IpAddress(
        'ipRangeStartv6',
        null,
        $zerotier_network['ipAssignmentPoolsv6']['ipRangeStart'],
        'V6'
    ))->setHelp('From');

    $group->add(new Form_IpAddress(
        'ipRangeEndv6',
        null,
        $zerotier_network['ipAssignmentPoolsv6']['ipRangeEnd'],
        'V6'
    ))->setHelp('To');
    $section->add($group);
    if ($config['installedpackages']['zerotier']['config'][0]['experimental']) {
        $section->addInput(new Form_Checkbox(
            'allowPassiveBridging',
            'Allow Passive Bridging',
            'Allow any member to bridge (very experimental)',
            $zerotier_network["allowPassiveBridging"]
        ));
    }
    $section->addInput(new Form_Input(
        'multicastLimit',
        'Multicast Limit',
        'number',
        $zerotier_network['multicastLimit'],
        ['min' => '0']
    ))->setHelp("Maximum recipients for a multicast packet.");
    $section->addInput(new Form_Input(
        'Routes',
        'Routes',
        'text',
        $zerotier_network['Routes'],
        ['min' => '0']
    ))->setHelp("Comma separated prefix list.");
    $section->addInput(new Form_Checkbox(
        'allowAnyProtocol',
        'Allow Any Protocol',
        'This option overrides the other protocol selections.',
        $zerotier_network["allowAnyProtocol"]
    ));
    $section->addInput(new Form_Checkbox(
        'AllowIPv4',
        'Allow IPv4',
        'Allow IPv4 and ARP frame types.',
        $zerotier_network["AllowIPv4"]
    ));
    $section->addInput(new Form_Checkbox(
        'AllowIPv6',
        'Allow IPv6',
        'Allow IPv6 frame types.',
        $zerotier_network["AllowIPv6"]
    ));
    $form->add($section);
    print($form);
else:
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">Zerotier Controller Networks</h2>
    </div>
    <div class="table-responsive panel-body">
        <table class="table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th>Network</th>
                    <th>Type</th>
                    <th>Members</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $networks = zerotier_controller_listnetworks();
                    //   $networks = [];
                    //  print_r($networks);
                    foreach($networks as $network) {
                ?>
                    <tr>
                        <td><a href="zerotier_controller_network.php?Network=<?php print($network->id); ?>"><?php print($network->id); print("</a><br />"); print("<strong>".$network->name."</strong>"); ?></td>
                        <td><?php print($network->private ? 'PRIVATE' : 'PUBLIC'); ?></td>
                        <td><span data-toggle="popover" data-trigger="hover focus" title="Member Detials" data-content="<?php print('Active: ' . $network->activeMemberCount . '<br>Authorized: ' . $network->authorizedMemberCount . '<br> Total: ' . $network->totalMemberCount); ?>" data-html="true"><?php print($network->activeMemberCount . '/' . $network->authorizedMemberCount . '/' . $network->totalMemberCount); ?></span></td>
                        <td>
                            <a href="?act=del&amp;Network=<?=$network->id;?>" class="fa fa-trash" title="<?=gettext('Remove Network')?>" usepost></a>
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
    <a href="zerotier_controller.php?act=new" class="btn btn-sm btn-success btn-sm">
        <i class="fa fa-plus icon-embed-btn"></i> Create
    </a>
</nav>
<?php
endif;
include("foot.inc"); ?>
