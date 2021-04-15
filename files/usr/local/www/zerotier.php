<?php
require_once("config.inc");
require_once("guiconfig.inc");
require_once("zerotier.inc");

$pgtitle = array(gettext("VPN"), gettext("Zerotier"), gettext("Configuration"));
$pglinks = array("", "pkg_edit.php?xml=zerotier.xml", "@self");
require("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Networks"), false, "zerotier_networks.php");
$tab_array[] = array(gettext("Peers"), false, "zerotier_peers.php");
$tab_array[] = array(gettext("Controller"), false, "zerotier_controller.php");
$tab_array[] = array(gettext("Configuration"), true, "zerotier.php");
add_package_tabs("Zerotier", $tab_array);
display_top_tabs($tab_array);

if (!is_array($config['installedpackages']['zerotier'])) {
	$config['installedpackages']['zerotier'] = array();
}

if (!is_array($config['installedpackages']['zerotier']['config'])) {
    $config['installedpackages']['zerotier']['config'] = array();
}

if($_POST['save']) {
    if(isset($_POST['enable'])) {
        $config['installedpackages']['zerotier']['config'][0]['enable'] = 'yes';

        zerotier_start();
    }
    else {
        $config['installedpackages']['zerotier']['config'][0]['enable'] = NULL;

        zerotier_kill();
    }

    if(isset($_POST['enableExperimental'])) {
        $config['installedpackages']['zerotier']['config'][0]['experimental'] = 'yes';
    }
    else {
        $config['installedpackages']['zerotier']['config'][0]['experimental'] = NULL;
    }
  
    write_config(gettext("Update enable Zerotier."));

    header("Location: zerotier.php");
}

if ($config['installedpackages']['zerotier']['config'][0]['enable'] != 'yes' || !is_service_running("zerotier")) {
    print_info_box(gettext("The Zerotier service is not running."), "warning", false);
}


$enable['mode'] = $config['installedpackages']['zerotier']['config'][0]['enable'];
$enable['experimental'] = $config['installedpackages']['zerotier']['config'][0]['experimental'];

if ($config['installedpackages']['zerotier']['config'][0]['enable'] == 'yes' && is_service_running("zerotier")) {
    $status = zerotier_status();
}
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Address: <?php print($status->address); ?></h2></div>
	<div class="panel-body">
		<dl class="dl-horizontal">
        <dt><?php print(gettext("Version")); ?><dt><dd><?php print($status->version) ?></dd>
        </dl>
    </div>
</div>

<?php

$form = new Form();
$section = new Form_Section('Enable Zerotier');
$section->addInput(new Form_Checkbox(
                'enable',
                'Enable',
                'Enable zerotier client and controller.',
                $enable['mode']
            ));
$form->add($section);
$section = new Form_Section('Enable Experimental Options');
$section->addInput(new Form_Checkbox(
                'enableExperimental',
                'Enable',
                'Enable zerotier client and controller experimental fields.',
                $enable['experimental']
            ))->setHelp('This will enable all experimental field options to be displayed and proccessed.');
$form->add($section);
print($form);
include("foot.inc");
?>
