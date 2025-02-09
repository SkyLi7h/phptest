<?php
date_default_timezone_set("Europe/Brussels");
if (! @include_once("config.php")) 
		throw new Exception ("config.php est introuvable !");
	
require_once "entity/village.php";

$bdd = new PDO('mysql:host='.$HOST.';dbname='.$DBNAME.';charset=utf8', $LOGIN, $PASS);

$run = false;
function runServ()
{
	GLOBAL $bdd, $UNITES, $run, $TEMPSDEPCASE, $TEMPLATE;
	$run = true;
	$listDeplacements = $bdd->query('SELECT * FROM deplacement');

	if($listDeplacements)
	{
		while($deplacement = $listDeplacements->fetch())
		{
			$maintenant = time();						
			if($deplacement["tpsArrive"] <= $maintenant)
			{
					
				$joueurOriMsg = $bdd->query('SELECT * FROM village WHERE villageId = '.$deplacement["idVillageOri"]);
				$joueurOriMsg = $joueurOriMsg->fetch();
				
				$joueurDestMsg = $bdd->query('SELECT * FROM village WHERE villageId = '.$deplacement["idVillageDest"]);
				$joueurDestMsg = $joueurDestMsg->fetch();
				
				if($deplacement["type"] == "combat")
				{
					
					$villageDest = refreshDonneesVillage($deplacement["idVillageDest"]);
					
					//Init
					$forceAttaquant = 0;
					$forceDefenseur = 0;
					
					$atkChance = (rand ( 90 , 110 ))/100;				
					
					//Force de l'attaquant
					$forceAttaquant += $UNITES["Paysans"]["ATK"]  * $deplacement["paysans"];
					$forceAttaquant += $UNITES["Lance-pierre"]["ATK"]  * $deplacement["lancePierre"];
					$forceAttaquant += $UNITES["Guerrier"]["ATK"]  * $deplacement["guerrier"];
					$forceAttaquant += $UNITES["Archer"]["ATK"]  * $deplacement["archer"];
					$forceAttaquant += $UNITES["Hache"]["ATK"]  * $deplacement["hache"];
					$forceAttaquant += $UNITES["Piquier"]["ATK"]  * $deplacement["piquier"];
					$forceAttaquant += $UNITES["HommeDeMain"]["ATK"]  * $deplacement["hommeDeMain"];
					$forceAttaquant += $UNITES["Chevalier"]["ATK"]  * $deplacement["chevalier"];
					$forceAttaquant += $UNITES["Catapulte"]["ATK"]  * $deplacement["catapulte"];
					$forceAttaquant *= $atkChance;
					
					//Force du défenseur
					$forceDefenseur += $UNITES["Paysans"]["ATK"]  * $villageDest->getPaysans();
					$forceDefenseur += $UNITES["Lance-pierre"]["ATK"]  * $villageDest->getLancePierre();
					$forceDefenseur += $UNITES["Guerrier"]["ATK"]  * $villageDest->getGuerrier();
					$forceDefenseur += $UNITES["Archer"]["ATK"]  * $villageDest->getArcher();
					$forceDefenseur += $UNITES["Hache"]["ATK"]  * $villageDest->getHache();
					$forceDefenseur += $UNITES["Piquier"]["ATK"]  * $villageDest->getPiquier();
					$forceDefenseur += $UNITES["HommeDeMain"]["ATK"]  * $villageDest->getHommeDeMain();
					$forceDefenseur += $UNITES["Chevalier"]["ATK"]  * $villageDest->getChevalier();
					$forceDefenseur += $UNITES["Catapulte"]["ATK"]  * $villageDest->getCatapulte();
					
					//Calcul combat
					$combat = $forceAttaquant - $forceDefenseur;
					
					
					//Si attaquant gagne
					if($combat > 0)
					{						
						//Pourcentage force restant
						$pourcRest = $combat / $forceAttaquant;
						
						//Calcul restant de l'armée
						$paysansRest = round($deplacement["paysans"] * $pourcRest);
						$lancePierreRest = round($deplacement["lancePierre"] * $pourcRest);
						$guerrierRest = round($deplacement["guerrier"] * $pourcRest);
						$archerRest = round($deplacement["archer"] * $pourcRest);
						$hacheRest = round($deplacement["hache"] * $pourcRest);
						$piquierRest = round($deplacement["piquier"] * $pourcRest);
						$hommeDeMainRest = round($deplacement["hommeDeMain"] * $pourcRest);
						$chevalierRest = round($deplacement["chevalier"] * $pourcRest);					
						$catapulteRest = round($deplacement["catapulte"] * $pourcRest);
						
						//Calcul butain transportable
						$capaciteButain = 0;
						$capaciteButain += $UNITES["Paysans"]["TRA"]  * $deplacement["paysans"];
						$capaciteButain += $UNITES["Lance-pierre"]["TRA"]  * $deplacement["lancePierre"];
						$capaciteButain += $UNITES["Guerrier"]["TRA"]  * $deplacement["guerrier"];
						$capaciteButain += $UNITES["Archer"]["TRA"]  * $deplacement["archer"];
						$capaciteButain += $UNITES["Hache"]["TRA"]  * $deplacement["hache"];
						$capaciteButain += $UNITES["Piquier"]["TRA"]  * $deplacement["piquier"];
						$capaciteButain += $UNITES["HommeDeMain"]["TRA"]  * $deplacement["hommeDeMain"];
						$capaciteButain += $UNITES["Chevalier"]["TRA"]  * $deplacement["chevalier"];
						$capaciteButain += $UNITES["Catapulte"]["TRA"]  * $deplacement["catapulte"];
																
						//Calcul butain////////
						if(($villageDest->getBois() + $villageDest->getPierre() + $villageDest->getMetal()) <= $capaciteButain)
						{
							$butainBois = $villageDest->getBois();
							$butainPierre = $villageDest->getPierre();
							$butainMetal = $villageDest->getMetal();
							
							$restVillageDestBois = 0;
							$restVillageDestPierre = 0;
							$restVillageDestMetal = 0;
						}
						else
						{
							$rest = 0;
							
							$butainBois = $capaciteButain / 3;
							$butainPierre = $capaciteButain / 3;
							$butainMetal = $capaciteButain / 3;
							
							if($butainBois > $villageDest->getBois())
							{
								$rest = $butainBois - $villageDest->getBois();
								$butainBois = $villageDest->getBois();
								$butainPierre += $rest;
							}
							
							if($butainPierre > $villageDest->getPierre())
							{
								$rest = $butainPierre - $villageDest->getPierre();
								$butainPierre = $villageDest->getPierre();
								$butainMetal += $rest;
							}
							
							if($butainMetal > $villageDest->getMetal())
							{
								$rest = $butainMetal - $villageDest->getMetal();
								$butainMetal = $villageDest->getMetal();
							}
							
							if($rest > 0)
							{
								$butainBois += $rest;
								
								if($butainBois > $villageDest->getBois())
								{
									$rest = $butainBois - $villageDest->getBois();
									$butainBois = $villageDest->getBois();
									$butainPierre += $rest;
								}
							}
							
							
							$restVillageDestBois = $villageDest->getBois() - $butainBois;
							$restVillageDestPierre = $villageDest->getPierre() - $butainPierre;
							$restVillageDestMetal = $villageDest->getMetal() - $butainMetal;
						}					
						
						$type = "retourPillage";
						
						$distance = calculDistance($deplacement["idVillageOri"], $deplacement["idVillageDest"]);
						$tempsArrive = time() + ($distance * $TEMPSDEPCASE);
						
						$bdd->query('UPDATE village set bois='.$restVillageDestBois.', pierre='.$restVillageDestPierre.', metal='.$restVillageDestMetal.', paysans=0, lancePierre=0, guerrier=0, archer=0, hache=0, piquier=0, hommeDeMain=0, chevalier=0, catapulte=0 where villageId='.$deplacement["idVillageDest"]);					
						$bdd->query('DELETE from deplacement where deplacementId ='.$deplacement["deplacementId"]);
						
						$bdd->query("INSERT INTO deplacement(type, idVillageOri, idVillageDest, tpsArrive, paysans, lancePierre, guerrier, archer, hache, piquier, hommeDeMain, chevalier, catapulte, bois, pierre, metal) VALUES('". $type ."', ". $deplacement["idVillageDest"] .", ". $deplacement["idVillageOri"] .", ". $tempsArrive .", ". $paysansRest .", ". $lancePierreRest .", ". $guerrierRest .", ". $archerRest .", ". $hacheRest .", ". $piquierRest .", ". $hommeDeMainRest .", ". $chevalierRest .", ". $catapulteRest .", ". $butainBois .", ". $butainPierre .", ". $butainMetal .")");
						
						//Rapport tab attaquant
						$rapportTabAtk = '<table class="RapportRecapUnite">';
							$rapportTabAtk .= '<tr>';
								$rapportTabAtk .= '<td>Unité :</td>';
										foreach ($UNITES as $unite)
											{
												$rapportTabAtk.= '<td id="tooltip" title="'. $unite["nom"] .'"><img width="15px" src="template/'. $TEMPLATE .'/images/unites/'.$unite["img"].'"></td>';
											}
							$rapportTabAtk .= '</tr>';
							$rapportTabAtk .= '<tr>';
								$rapportTabAtk .= '<td>Nombre :</td>';
								$rapportTabAtk .= '<td>'. $deplacement["paysans"] .'</td>';								
								$rapportTabAtk .= '<td>'. $deplacement["lancePierre"] .'</td>';								
								$rapportTabAtk .= '<td>'. $deplacement["guerrier"] .'</td>';								
								$rapportTabAtk .= '<td>'. $deplacement["archer"] .'</td>';								
								$rapportTabAtk .= '<td>'. $deplacement["hache"] .'</td>';								
								$rapportTabAtk .= '<td>'. $deplacement["piquier"] .'</td>';								
								$rapportTabAtk .= '<td>'. $deplacement["hommeDeMain"] .'</td>';								
								$rapportTabAtk .= '<td>'. $deplacement["chevalier"] .'</td>';								
								$rapportTabAtk .= '<td>'. $deplacement["catapulte"] .'</td>';								
							$rapportTabAtk .= '</tr>';
							$rapportTabAtk .= '<tr>';
								$rapportTabAtk .= '<td>Perte :</td>';
								$rapportTabAtk .= '<td id="perte">'. ($deplacement["paysans"] - $paysansRest) .'</td>';								
								$rapportTabAtk .= '<td id="perte">'. ($deplacement["lancePierre"] - $lancePierreRest) .'</td>';								
								$rapportTabAtk .= '<td id="perte">'. ($deplacement["guerrier"] - $guerrierRest).'</td>';								
								$rapportTabAtk .= '<td id="perte">'. ($deplacement["archer"] - $archerRest).'</td>';								
								$rapportTabAtk .= '<td id="perte">'. ($deplacement["hache"] - $hacheRest).'</td>';								
								$rapportTabAtk .= '<td id="perte">'. ($deplacement["piquier"] - $piquierRest).'</td>';								
								$rapportTabAtk .= '<td id="perte">'. ($deplacement["hommeDeMain"] - $hommeDeMainRest).'</td>';								
								$rapportTabAtk .= '<td id="perte">'. ($deplacement["chevalier"] - $chevalierRest).'</td>';								
								$rapportTabAtk .= '<td id="perte">'. ($deplacement["catapulte"] - $catapulteRest).'</td>';								
							$rapportTabAtk .= '</tr>';
							$rapportTabAtk .= '<tr>';
								$rapportTabAtk .= '<td>Restant :</td>';
								$rapportTabAtk .= '<td>'. $paysansRest .'</td>';								
								$rapportTabAtk .= '<td>'. $lancePierreRest .'</td>';								
								$rapportTabAtk .= '<td>'. $guerrierRest.'</td>';								
								$rapportTabAtk .= '<td>'. $archerRest.'</td>';								
								$rapportTabAtk .= '<td>'. $hacheRest.'</td>';								
								$rapportTabAtk .= '<td>'. $piquierRest.'</td>';								
								$rapportTabAtk .= '<td>'. $hommeDeMainRest.'</td>';								
								$rapportTabAtk .= '<td>'. $chevalierRest.'</td>';								
								$rapportTabAtk .= '<td>'. $catapulteRest.'</td>';								
							$rapportTabAtk .= '</tr>';
						$rapportTabAtk .= '</table>';
						
						//Rapport tab defenseur
						$rapportTabDef = '<table class="RapportRecapUnite">';
							$rapportTabDef .= '<tr>';
								$rapportTabDef .= '<td>Unité :</td>';
										foreach ($UNITES as $unite)
											{
												$rapportTabDef.= '<td id="tooltip" title="'. $unite["nom"] .'"><img width="15px" src="template/'. $TEMPLATE .'/images/unites/'.$unite["img"].'"></td>';
											}
							$rapportTabDef .= '</tr>';
							$rapportTabDef .= '<tr>';
								$rapportTabDef .= '<td>Nombre :</td>';
								$rapportTabDef .= '<td>'. $villageDest->getPaysans() .'</td>';								
								$rapportTabDef .= '<td>'. $villageDest->getLancePierre() .'</td>';								
								$rapportTabDef .= '<td>'. $villageDest->getGuerrier() .'</td>';								
								$rapportTabDef .= '<td>'.  $villageDest->getArcher() .'</td>';								
								$rapportTabDef .= '<td>'.  $villageDest->getHache() .'</td>';								
								$rapportTabDef .= '<td>'.  $villageDest->getPiquier() .'</td>';								
								$rapportTabDef .= '<td>'.  $villageDest->getHommeDeMain() .'</td>';								
								$rapportTabDef .= '<td>'.  $villageDest->getChevalier() .'</td>';								
								$rapportTabDef .= '<td>'.  $villageDest->getCatapulte() .'</td>';								
							$rapportTabDef .= '</tr>';
							$rapportTabDef .= '<tr>';
								$rapportTabDef .= '<td>Perte :</td>';
								$rapportTabDef .= '<td id="perte">'. $villageDest->getPaysans() .'</td>';								
								$rapportTabDef .= '<td id="perte">'. $villageDest->getLancePierre() .'</td>';								
								$rapportTabDef .= '<td id="perte">'. $villageDest->getGuerrier() .'</td>';								
								$rapportTabDef .= '<td id="perte">'.  $villageDest->getArcher() .'</td>';								
								$rapportTabDef .= '<td id="perte">'.  $villageDest->getHache() .'</td>';								
								$rapportTabDef .= '<td id="perte">'.  $villageDest->getPiquier() .'</td>';								
								$rapportTabDef .= '<td id="perte">'.  $villageDest->getHommeDeMain() .'</td>';								
								$rapportTabDef .= '<td id="perte">'.  $villageDest->getChevalier() .'</td>';								
								$rapportTabDef .= '<td id="perte">'.  $villageDest->getCatapulte() .'</td>';	
							$rapportTabDef .= '</tr>';
							$rapportTabDef .= '<tr>';
								$rapportTabDef .= '<td>Restant :</td>';
								$rapportTabDef .= '<td>0</td>';								
								$rapportTabDef .= '<td>0</td>';								
								$rapportTabDef .= '<td>0</td>';								
								$rapportTabDef .= '<td>0</td>';							
								$rapportTabDef .= '<td>0</td>';					
								$rapportTabDef .= '<td>0</td>';							
								$rapportTabDef .= '<td>0</td>';						
								$rapportTabDef .= '<td>0</td>';				
								$rapportTabDef .= '<td>0</td>';							
							$rapportTabDef .= '</tr>';
						$rapportTabDef .= '</table>';
						
						//Construction rapport attaquant//
						$sujet = "Rapport de combat : " . $villageDest->getNom();
						$titre = '<div id=\"titreRapportCombat\">Vous avez gagné</div>';
						$sousTitre = '<div id=\"sousTitreRapportCombat\">'.$joueurOriMsg["nom"].'</div>';
						<img width="25px" src="template/<?php echo $TEMPLATE; ?>/images/ressources/wood.png"><span id="bois"><?php echo floor($village->getBois());?></span>
						<img width="25px" src="template/<?php echo $TEMPLATE; ?>/images/ressources/stone.png"><span id="pierre"><?php echo floor($village->getPierre());?></span>
						<img width="25px" src="template/<?php echo $TEMPLATE; ?>/images/ressources/iron.png"><span id="metal"><?php echo floor($village->getMetal());?></span>
						$gain = '';
						$message = $titre . $rapportTabAtk . $rapportTabDef;
						
						$bdd->query("INSERT INTO message(joueurOri, joueurDest, sujet, message, temps, type) VALUES(0, ". $joueurOriMsg["joueurId"] .", '". $sujet ."', '". $message ."', ". time() .", 'rapportCombat')");
						
						//Construction rapport def//
						$sujet = "Pillage provenant de : " . $joueurOriMsg["nom"];
						$titre = '<div id=\"titreRapportCombat\">Vous avez perdu</div>';
						$sousTitre = '<div id=\"sousTitreRapportCombat\">'. $joueurDestMsg["nom"] .'</div>';
						$message = $titre . $rapportTabAtk . $rapportTabDef;
						
						$bdd->query("INSERT INTO message(joueurOri, joueurDest, sujet, message, temps, type) VALUES(0, ". $joueurDestMsg["joueurId"] .", '". $sujet ."', '". $message ."', ". time() .", 'rapportCombat')");
					}
					else if($combat < 0) //Defenseur gagne
					{
						//Résultat combat en positif
						$combat *= -1;
						//Pourcentage force restant
						$pourcRest = $combat / $forceDefenseur;
						
						//Calcul restant de l'armée
						$paysansRest = round($villageDest->getPaysans() * $pourcRest);
						$lancePierreRest = round($villageDest->getLancePierre() * $pourcRest);
						$guerrierRest = round($villageDest->getGuerrier() * $pourcRest);
						$archerRest = round($villageDest->getArcher() * $pourcRest);
						$hacheRest = round($villageDest->getHache() * $pourcRest);
						$piquierRest = round($villageDest->getPiquier() * $pourcRest);
						$hommeDeMainRest = round($villageDest->getHommeDeMain() * $pourcRest);
						$chevalierRest = round($villageDest->getChevalier() * $pourcRest);					
						$catapulteRest = round($villageDest->getCatapulte() * $pourcRest);
						
						$bdd->query('UPDATE village set paysans='.$paysansRest.', lancePierre='.$lancePierreRest.', guerrier='.$guerrierRest.', archer='.$archerRest.', hache='.$hacheRest.', piquier='.$piquierRest.', hommeDeMain='.$hommeDeMainRest.', chevalier='.$chevalierRest.', catapulte='.$catapulteRest.' where villageId='.$deplacement["idVillageDest"]);
						$bdd->query('DELETE from deplacement where deplacementId ='.$deplacement["deplacementId"]);
						
						$sujet = "Rapport de combat : " . $villageDest->getNom();
						$message = "Vous avez perdu !";
						$bdd->query("INSERT INTO message(joueurOri, joueurDest, sujet, message, temps, type) VALUES(0, ". $joueurDestMsg["joueurId"] .", '". $sujet ."', '". $message ."', ". time() .", 'rapportCombat')");
					}
					else
					{
						$bdd->query('UPDATE village set paysans=0, lancePierre=0, guerrier=0, archer=0, hache=0, piquier=0, hommeDeMain=0, chevalier=0, catapulte=0 where villageId='.$deplacement["idVillageDest"]);
						$bdd->query('DELETE from deplacement where deplacementId ='.$deplacement["deplacementId"]);
						
						$sujet = "Rapport de combat : " . $villageDest->getNom();
						$message = "Egalité !";
						$bdd->query("INSERT INTO message(joueurOri, joueurDest, sujet, message, temps, type) VALUES(0, ". $joueurDestMsg["joueurId"] .", '". $sujet ."', '". $message ."', ". time() .", 'rapportCombat')");
					}			
				}
				else if($deplacement["type"] == "retourPillage")
				{			
					$bdd->query('UPDATE village set bois=bois+'.$deplacement["bois"].', pierre=pierre+'.$deplacement["pierre"].', metal=metal+'.$deplacement["metal"].', paysans=paysans+'.$deplacement["paysans"].', lancePierre=lancePierre+'.$deplacement["lancePierre"].', guerrier=guerrier+'.$deplacement["guerrier"].', archer=archer+'.$deplacement["archer"].', hache=hache+'.$deplacement["hache"].', piquier=piquier+'.$deplacement["piquier"].', hommeDeMain=hommeDeMain+'.$deplacement["hommeDeMain"].', chevalier=chevalier+'.$deplacement["chevalier"].', catapulte=catapulte+'.$deplacement["catapulte"].' where villageId='.$deplacement["idVillageDest"]);
					$bdd->query('DELETE from deplacement where deplacementId ='.$deplacement["deplacementId"]);
				}
			}
		}
	}
	
	$run = false;
	
}

function calculDistance($ori, $dest)
{
	GLOBAL $bdd, $UNITES;
	$villageOri = $bdd->query('SELECT * FROM carte where villageId='.$ori);
	$villageOri = $villageOri->fetch();
	
	$villageDest = $bdd->query('SELECT * FROM carte where villageId='.$dest);
	$villageDest = $villageDest->fetch();
	
	$distX = $villageOri["x"] - $villageDest["x"];
	$distY = $villageOri["y"] - $villageDest["y"];

	$distance = sqrt( $distX*$distX + $distY*$distY );

	return $distance;
}

function refreshDonneesVillage($villageId)
{
		global $bdd, $BATIMENTS, $UNITES;
		
		$village = new village($villageId, "", "", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
		
		//Recup donnée villages et maj si modif manuelle depuis panel ou bdd ou si add construction( ressources retirées )
		$reponseVillage = $bdd->query('SELECT * from village where villageId ='.$villageId);
		$donneesVillage = $reponseVillage->fetch();
		
		$village->setNom($donneesVillage["nom"]);
		
		$village->setScierie($donneesVillage["scierie"]);
		$village->setCarriere($donneesVillage["carriere"]);
		$village->setMine($donneesVillage["mine"]);
		$village->setChateau($donneesVillage["chateau"]);
		$village->setCaserne($donneesVillage["caserne"]);
		
		$village->setBois($donneesVillage["bois"]);
		$village->setPierre($donneesVillage["pierre"]);
		$village->setMetal($donneesVillage["metal"]);
		
		$village->setPaysans($donneesVillage["paysans"]);
		$village->setLancePierre($donneesVillage["lancePierre"]);
		$village->setGuerrier($donneesVillage["guerrier"]);
		$village->setArcher($donneesVillage["archer"]);
		$village->setHache($donneesVillage["hache"]);
		$village->setPiquier($donneesVillage["piquier"]);
		$village->setHommeDeMain($donneesVillage["hommeDeMain"]);
		$village->setChevalier($donneesVillage["chevalier"]);
		$village->setCatapulte($donneesVillage["catapulte"]);
		
		$tpsADecompterBois = 0;
		$tpsADecompterPierre = 0;
		$tpsADecompterMine = 0;
		
		//Recup données de construction
		$reponseConstruction = $bdd->query('SELECT * from construction where villageId ='.$villageId);
		while($donneesConstruction = $reponseConstruction->fetch())
		{
			if($donneesConstruction["tempsDeb"] + $donneesConstruction["temps"] <= time())
			{
				switch ($donneesConstruction["batiment"]) 
				{
					case "Scierie":
						$village->upScierie();
						$gainBoisEnPlus = (($BATIMENTS["Scierie"]["gain"][$village->getScierie()]/3600)*(time() - ($donneesConstruction["tempsDeb"] + $donneesConstruction["temps"])));
						$tpsADecompterBois = time() - ($donneesConstruction["tempsDeb"] + $donneesConstruction["temps"]);
						break;
					case "Carriere":
						$village->upCarriere();
						$gainPierreEnPlus = (($BATIMENTS["Carriere"]["gain"][$village->getCarriere()]/3600)*(time() - ($donneesConstruction["tempsDeb"] + $donneesConstruction["temps"])));
						$tpsADecompterPierre = time() - ($donneesConstruction["tempsDeb"] + $donneesConstruction["temps"]);
						break;
					case "Mine":
						$village->upMine();
						$gainMetalEnPlus = (($BATIMENTS["Mine"]["gain"][$village->getMine()]/3600)*(time() - ($donneesConstruction["tempsDeb"] + $donneesConstruction["temps"])));
						$tpsADecompterMine = time() - ($donneesConstruction["tempsDeb"] + $donneesConstruction["temps"]);
						break;	
					case "Château":
						$village->upChateau();
						break;	
					case "Caserne":
						$village->upCaserne();
						break;	
				}
				
				$bdd->query('DELETE from construction where constructionId ='.$donneesConstruction["constructionId"]);
			}			
		}
		
		//Tps ecoulé depuis la dernière maj des ressources en secondes
		$tpsEcoule = time() - $donneesVillage["dernMaj"];
		if($tpsADecompterBois != 0)
		{
			$tpsEcoule -= $tpsADecompterBois;
			$boisTot = $village->getBois() + (($BATIMENTS["Scierie"]["gain"][$village->getScierie()-1]/3600)*$tpsEcoule);
			$boisTot += $gainBoisEnPlus;
		}
		else
		{
			$boisTot = $village->getBois() + (($BATIMENTS["Scierie"]["gain"][$village->getScierie()]/3600)*$tpsEcoule);
		}
		
		if($tpsADecompterPierre != 0)
		{
			$tpsEcoule -= $tpsADecompterPierre;
			$pierreTot = $village->getPierre() + (($BATIMENTS["Carriere"]["gain"][$village->getCarriere()-1]/3600)*$tpsEcoule);
			$pierreTot += $gainPierreEnPlus;
		}
		else
		{
			$pierreTot = $village->getPierre() + (($BATIMENTS["Carriere"]["gain"][$village->getCarriere()]/3600)*$tpsEcoule);
		}
		
		if($tpsADecompterMine != 0)
		{
			$tpsEcoule -= $tpsADecompterMine;
			$metalTot = $village->getMetal() + (($BATIMENTS["Mine"]["gain"][$village->getMine()-1]/3600)*$tpsEcoule);
			$metalTot += $gainMetalEnPlus;
		}
		else
		{
			$metalTot = $village->getMetal() + (($BATIMENTS["Mine"]["gain"][$village->getMine()]/3600)*$tpsEcoule);
		}
		
		$village->setBois($boisTot);
		$village->setPierre($pierreTot);
		$village->setMetal($metalTot);
		
		
		// Recup données de recrutement
		$reponseRecrutement = $bdd->query('SELECT * from recrutement where villageId ='.$villageId);
		while($donneesRecrutement = $reponseRecrutement->fetch())
		{
			$termineA = (($donneesRecrutement["nbUnite"] * $UNITES[$donneesRecrutement["unite"]]["cout"]["temps"]) + $donneesRecrutement["tpsDeb"]);
			
			if($termineA <= time())
			{
				switch ($donneesRecrutement["unite"]) 
				{
					case "Paysans":
						$village->addPaysans($donneesRecrutement["nbUnite"]);						
						break;
					case "Lance-pierre":
						$village->addLancePierre($donneesRecrutement["nbUnite"]);		
						break;	
					case "Guerrier":
						$village->addGuerrier($donneesRecrutement["nbUnite"]);		
						break;
					case "Archer":
						$village->addArcher($donneesRecrutement["nbUnite"]);		
						break;
					case "Hache":
						$village->addHache($donneesRecrutement["nbUnite"]);		
						break;
					case "Piquier":
						$village->addPiquier($donneesRecrutement["nbUnite"]);		
						break;
					case "HommeDeMain":
						$village->addHommeDeMain($donneesRecrutement["nbUnite"]);		
						break;
					case "Chevalier":
						$village->addChevalier($donneesRecrutement["nbUnite"]);		
						break;
					case "Catapulte":
						$village->addCatapulte($donneesRecrutement["nbUnite"]);		
						break;
				}
				
				$bdd->query('DELETE from recrutement where idRecrutement ='.$donneesRecrutement["idRecrutement"]);
			}
		}
		
		//Maj du village dans la bdd
		$bdd->query('UPDATE village set bois='.$boisTot.', pierre='.$pierreTot.', metal='.$metalTot.', scierie='.$village->getScierie().', carriere='.$village->getCarriere().', mine='.$village->getMine().', chateau='.$village->getChateau().', caserne='.$village->getCaserne().', paysans='.$village->getPaysans().', lancePierre='.$village->getLancePierre().', guerrier='.$village->getGuerrier().', archer='.$village->getArcher().', hache='.$village->getHache().', piquier='.$village->getPiquier().', hommeDeMain='.$village->getHommeDeMain().', chevalier='.$village->getChevalier().', catapulte='.$village->getCatapulte().', dernMaj='. time() .' where villageId='.$villageId);
		
		return $village;
}

while(1)
{		
	if(!$run)
		runServ();	
	sleep(1);
}










?>