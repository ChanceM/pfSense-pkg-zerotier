<?php
require_once("guiconfig.inc");
require_once("zerotier.inc");

function sort_roles($a, $b) {
    if($a->role == $b->role){ return 0 ; }
	return ($a->role < $b->role) ? 1 : -1;
}

$pgtitle = array(gettext("VPN"), gettext("Zerotier"), gettext("Peers"));
$pglinks = array("", "pkg_edit.php?xml=zerotier.xml", "@self");
require("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Networks"), false, "zerotier_networks.php");
$tab_array[] = array(gettext("Peers"), true, "zerotier_peers.php");
$tab_array[] = array(gettext("Controller"), false, "zerotier_controller.php");
$tab_array[] = array(gettext("Configuration"), false, "zerotier.php");
add_package_tabs("Zerotier", $tab_array);
display_top_tabs($tab_array);

if (!is_service_running("zerotier")) {
    print_info_box(gettext("The Zerotier service is not running."), "warning", false);
}

?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">Zerotier Peers</h2>
    </div>
    <div class="table-responsive panel-body">
        <table class="table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th>ZT Address</th>
                    <th>Path</th>
                    <th>Latency</th>
                    <th>Version</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $peers = zerotier_listpeers();
                    usort($peers, 'sort_roles');
                    foreach($peers as $peer) {
                ?>
                    <tr>
                        <td><?php print($peer->address); ?></td>
                        <td><?php print($peer->paths[0]->address); ?></td>
                        <td><?php print($peer->latency); ?></td>
                        <td><?php print($peer->version); ?></td>
                        <td><?php print($peer->role); ?></td>
                    </tr>
                <?php
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>
<?php include("foot.inc"); ?>
