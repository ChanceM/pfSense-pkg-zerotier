<?php
require_once("config.inc");
require_once("guiconfig.inc");
require_once("zerotier.inc");

function get_status_label($status) {
    $label = '';
    switch ($status) {
        case 'OK':
            $label = 'success';
            break;
        case 'ACCESS_DENIED':
            $label = 'danger';
            break;
        default:
            $label = 'default';
            break;
    }

    return $label;
}

$pgtitle = array(gettext("VPN"), gettext("Zerotier"), gettext("Configuration"));
$pglinks = array("", "pkg_edit.php?xml=zerotier.xml", "@self");
require("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Networks"), false, "zerotier_networks.php");
$tab_array[] = array(gettext("Peers"), false, "zerotier_peers.php");
$tab_array[] = array(gettext("Configuration"), true, "zerotier.php");
add_package_tabs("Zerotier", $tab_array);
display_top_tabs($tab_array);

if (!is_service_running("zerotier")) {
    print('<div class="alert alert-warning" role="alert"><strong>Zerotier</strong> service is not running.</div>');
}

if($_POST['save']) {

    if(isset($_POST['enable'])) {
        $config['installedpackages']['zerotier']['enabled'] = 'true';
        if (!is_service_running("zerotier")) {
            start_service("zerotier");
        }
    }
    else {
        $config['installedpackages']['zerotier']['enabled'] = 'false';
        zerotier_kill();
    }

    if(isset($_POST['enableExperimental'])) {
        $config['installedpackages']['zerotier']['experimental'] = 'true';
    }
    else {
        $config['installedpackages']['zerotier']['experimental'] = 'false';
    }

    write_config(gettext("Zerotier configuration updated."));

    header("Location: zerotier.php");
}

$enable['mode'] = ($config['installedpackages']['zerotier']['enabled'] == 'true');
$enable['experimental'] = ($config['installedpackages']['zerotier']['experimental'] == 'true');

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
