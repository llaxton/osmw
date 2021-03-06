<?php 
if (isset($_SESSION['authentification']))
{
    // vérification sur la session authentification 
    if (isset($_POST['OSSelect'])) {$_SESSION['opensim_select'] = trim($_POST['OSSelect']);}

	// *** LECTURE DES FICHIERS INI ***
    // *** Lecture Fichier Regions.ini ***
 	$filename2 = INI_Conf_Moteur($_SESSION['opensim_select'], "address")."Regions/".$FichierINIRegions;	 
	if (file_exists($filename2)) {$filename = $filename2;}
    else {;}

    $tableauIni = parse_ini_file($filename, true);
    if ($tableauIni == FALSE) {echo '<p>Error: Reading ini file '.$filename.'</p>';}

    // *** Lecture Fichier OpenSimDefaults ***
    $filename2 = INI_Conf_Moteur($_SESSION['opensim_select'], "address").$FichierINIOpensim;		 
	if (file_exists($filename2)) {$filename = $filename2 ;}
    else {;}

	// **** Recuperation du port http du serveur ******		
    if (!$fp = fopen($filename, "r")) 
    {
        echo '<p>Erreur: Ouverture du fichier '.$filename.'</p>';
    }

    $tabfich = file($filename); 
    
    for( $i = 1 ; $i < count($tabfich) ; $i++ )
    {
        $porthttp = strstr($tabfich[$i], "http_listener_port");
        
        if($porthttp)
        {
            $posEgal = strpos($porthttp,'=');
            $longueur = strlen($porthttp);
            $srvOS = substr($porthttp, $posEgal + 1);
        }
    }
    fclose($fp);
	// *** FIN LECTURE DES FICHIERS INI ***

    echo '<p class="pull-right"><span class="label label-danger">Espace Securise Niveau '.$_SESSION['privilege'].'</span></p>';
    echo '<h1>Gestion des Regions</h1>';
    echo '<div class="clearfix"></div>';

    echo '<p>Simulateur selectionne ';
    echo '<strong class="label label-info">'.$_SESSION['opensim_select'].' '.INI_Conf_Moteur($_SESSION['opensim_select'], "version").'</strong>';
    echo '</p>';
	//*****************************************************
	// Si NIV 1 - Verification Moteur Autorise ************
	if ($_SESSION['osAutorise'] != '')
	{
        $osAutorise = explode(";", $_SESSION['osAutorise']);
        // echo count($osAutorise);
        // echo $_SESSION['osAutorise'];
        for($i = 0; $i < count($osAutorise); $i++)
		{
            if(INI_Conf_Moteur($_SESSION['opensim_select'], "osAutorise") == $osAutorise[$i])
            {
                $moteursOK = "OK";
            }
        }
    }
	else {$moteursOK = "NOK";}

    /* ************************************ */
	$btnN1 = "disabled";
    $btnN2 = "disabled";
    $btnN3 = "disabled";
	if ($_SESSION['privilege'] == 4) {$btnN1 = ""; $btnN2 = ""; $btnN3 = "";} // Niv 4
	if ($_SESSION['privilege'] == 3) {$btnN1 = ""; $btnN2 = ""; $btnN3 = "";} // Niv 3
	if ($_SESSION['privilege'] == 2) {$btnN1 = ""; $btnN2 = "";}              // Niv 2
	if ($moteursOK == "OK")
    {
        if ($_SESSION['privilege'] == 1)
        {
            $btnN1 = "";
            $btnN2 = "";
            $btnN3 = "";
        }
    } // Niv 1 + SECURITE MOTEUR
    /* ************************************ */

    /* Selon ACTION bouton => CONSTRUCTION de la commande pour ENVOI sur la console via  SSH */
	if (isset($_POST['cmd']))
	{
        // *** Lecture Fichier OpenSimDefaults ***
        $filename2 = INI_Conf_Moteur($_SESSION['opensim_select'], "address").$FichierINIOpensim;			 
        if (file_exists($filename2)) {$filename = $filename2;}
        else {;}

        // **** Recuperation du port http du serveur ******		
		if (!$fp = fopen($filename,"r")) 
		{
            echo "Echec d'ouverture du fichier ".$filename;
        }
		
		$tabfich = file($filename); 
		
        for( $i = 1 ; $i < count($tabfich) ; $i++ )
		{
            $porthttp = strstr($tabfich[$i]," port = ");
            $access_password = strstr($tabfich[$i]," access_password = ");
			
            if ($porthttp)
			{
				$posEgal = strpos($porthttp, '=');
				$longueur = strlen($porthttp);
				$RemotePort = substr($porthttp, $posEgal + 1);
			}
			
            if ($access_password)
			{
				$posEgal = strpos($access_password, '=');
				$longueur = strlen($access_password);
				$access_password2 = trim(substr($access_password, $posEgal + 1));
				// $longueur2 = strlen($access_password2);
				// $Remote_access_password = substr($access_password2, 1,$longueur2-2 );			
			}
		}
		fclose($fp);
		// $myRemoteAdmin = new RemoteAdmin(trim($hostnameSSH), trim($RemotePort), trim($Remote_access_password));
        $myRemoteAdmin = new RemoteAdmin(trim($hostnameSSH), trim($RemotePort), trim($access_password2));

		// *** Affichage mode debug ***
		// echo '# '.$_POST['cmd'].' #<br />';
		
		// EXECUTION COMMANDE SYSTEME
		if($_POST['cmd'] == 'Start')
		{
            // TO DO
			// $cheminWIN = str_replace('/','\\',INI_Conf_Moteur($_SESSION['opensim_select'], "address"));
			// echo $cmd= 'START "Opensimulator" "'.$cheminWIN.'"osmw.bat';
            // $cmd = 'START "Opensimulator" inc"\"osmw.bat';
            $simulator = $_SESSION['opensim_select'];
            $cmd = 'START "Opensimulator" bat"\"'.$simulator.'.bat';
            exec_command($cmd);
        }

        // COMMANDE PAR REMOTE ADMIN
		if ($_POST['cmd'] == 'Region Root')
        {
            $parameters = array('command' => 'change region root');
            $myRemoteAdmin->SendCommand('admin_console_command', $parameters);
        }

		if ($_POST['cmd'] == 'Update Client')
        {
            $parameters = array('command' => 'force update');
            $myRemoteAdmin->SendCommand('admin_console_command', $parameters);
        }

		if ($_POST['cmd'] == 'Stop')
        {
            $parameters = array('command' => 'quit');
            $myRemoteAdmin->SendCommand('admin_console_command', $parameters);
        }

		if ($_POST['cmd'] == 'Restart')
        {
            $parameters = array('command' => 'restart');
            $myRemoteAdmin->SendCommand('admin_console_command', $parameters);
        }

		if ($_POST['cmd'] == 'Alerte General')
        {
            $parameters = array('message' => $_POST['msg_alert']);
            $myRemoteAdmin->SendCommand('admin_broadcast', $parameters);
        }			
	}	

    //******************************************************
    //  Affichage page principale
    //******************************************************
	// Formulaire de choix du moteur a selectionne
    // On se connecte a MySQL
	$db = mysql_connect($hostnameBDD, $userBDD, $passBDD);
	mysql_select_db($database,$db);
	
    $sql = 'SELECT * FROM moteurs';
	$req = mysql_query($sql) or die('Erreur SQL !<p>'.$sql.'</p>'.mysql_error());
    
    // echo '<h4>Selectionner un Simulateur</h4>';
	echo '<form class="form-group" method="post" action="">';
    echo '<div class="form-inline">';
    echo '<label for="OSSelect"></label>Select Simulator ';
    echo '<select class="form-control" name="OSSelect">';

    while($data = mysql_fetch_assoc($req))
    {
        $sel = "";
        if ($data['id_os'] == $_SESSION['opensim_select']) {$sel = "selected";}
        echo '<option value="'.$data['id_os'].'" '.$sel.'>'.$data['name'].' '.$data['version'].'</option>';
    }
    
    echo'</select>';
    echo' <button type="submit" class="btn btn-success"><i class="glyphicon glyphicon-ok"></i> Choisir</button>';
    echo '</div>';
    echo'</form>';
    mysql_close();

	// echo '<h4>Effectuer une actions sur le simulateur</h4>';
    echo '<form class="form-group" method="post" action="">';
    echo '<div class="btn-group" role="group" aria-label="...">';

    echo '<button type="submit" class="btn btn-default" value="Region Root" name="cmd" '.$btnN1.'>';
    echo '<i class="glyphicon glyphicon-th-large"></i> Region Root</button>';

    echo '<button type="submit" class="btn btn-default" value="Update Client" name="cmd" '.$btnN1.'>';
    echo '<i class="glyphicon glyphicon-random"></i> Update Client</button>';

    echo '<button type="submit" class="btn btn-default" value="Restart" name="cmd" '.$btnN2.'>';
    echo '<i class="glyphicon glyphicon-refresh"></i> Restart</button>';

    echo '<button type="submit" class="btn btn-default" value="Start" name="cmd" '.$btnN3.'>';
    echo '<i class="glyphicon glyphicon-ok"></i> Start</button>';

    echo '<button type="submit" class="btn btn-default" value="Stop" name="cmd" '.$btnN3.'>';
    echo '<i class="glyphicon glyphicon-remove"></i> Stop</button>';
	echo '</div>';
    echo '</form>';	

	// echo '<h4>Envoyer un message sur toutes les regions</h4>';
    echo '<form class="form-group" method="post" action="">';
    echo '<div class="btn-group " role="group" aria-label="...">';
	echo '<div class="input-group col-xs-6">';
	echo '<input type="text" class="form-control" name="msg_alert" placeholder="Message a toutes les regions connectees ...">';
	echo '<span class="input-group-btn">';
    echo '<button type="submit" class="btn btn-danger" value="Alerte General" name="cmd" '.$btnN2.'><i class="glyphicon glyphicon-bullhorn"></i> Alerte General</button>';
    echo '</span>';
    echo '</div>';
    echo '</div>';
    echo '</form>';

    echo '<p>Nombre total de regions <span class="badge">'.count($tableauIni).'</span>';

    echo '<table class="table table-hover">';
    echo '<tr>';
    echo '<th>Name</th>';
    echo '<th>Image</th>';
    echo '<th>Location</th>';
    echo '<th>Public IP/Host</th>';
    echo '<th>Port</th>';
    echo '<th>Teleport</th>';
    echo '<th>Status</th>';
    echo '</tr>';

	while (list($key, $val) = each($tableauIni))
	{
		$ImgMap = "http://".$hostnameSSH.":".trim($srvOS)."/index.php?method=regionImage".str_replace("-","",$tableauIni[$key]['RegionUUID']);
        if (Test_Url($ImgMap) == false)
        {
            $i = '<p class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i></p>';
            $ImgMap = "img/offline.jpg";
        }
        
        else
        {
            $i = '<p class="btn btn-success" ><i class="glyphicon glyphicon-ok"></i></p>';
        }
		
		echo '<tr>';
        echo '<td><h5>'.$key.'</h5></td>';
        echo '<td><img style="height:38px;" class="img-thumbnail" alt="" src="'.$ImgMap.'"></td>';
        echo '<td><h5><span class="badge">'.$tableauIni[$key]['Location'].'</span></h5></td>';
        echo '<td><h5>'.$tableauIni[$key]['ExternalHostName'].'</h5></td>';
        echo '<td><h5><span class="badge">'.$tableauIni[$key]['InternalPort'].'</span></h5></td>';
        // echo '<td><a class="btn btn-default" href="secondlife://'.$hypergrid.":".$key.'">Teleport</a></td>';
        echo '<td><a class="btn btn-default" href="secondlife://'.$key.'/128/128/25"><i class="glyphicon glyphicon-plane"></i> Teleport</a></td>';
        echo '<td>'.$i.'</td>';
        echo '</tr>';
	}
	echo '</table>';
}
else {header('Location: index.php');}
?>
