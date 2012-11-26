<?php
$spartan = $_GET['spartan']; //get spartans name
$weaponAssetUrl = "https://assets.halowaypoint.com/games/h4/damage-types/v1/"; //asset url for weapon images
$profileAssetUrl = "https://spartans.svc.halowaypoint.com/players/".rawurlencode($spartan)."/h4/spartans/fullbody?target=large"; //asset url for profile image
$emblemAssetUrl = "https://emblems.svc.halowaypoint.com/h4/emblems/"; //asset url for emblems

//get the spartans service record
$file = file_get_contents("https://stats.svc.halowaypoint.com/en-us/players/".rawurlencode($spartan)."/h4/servicerecord");

//load the xml from the service record
$halo = new SimpleXMLElement($file);

$bg = new Imagick("images/backgrounds/halo_bg.png"); //create the background image
$weapon = new Imagick($weaponAssetUrl . str_replace('{size}', 'large', $halo->FavoriteWeaponImageUrl->AssetUrl)); //create the weapon image
$emblem = new Imagick($emblemAssetUrl . str_replace('{size}', '120', $halo->EmblemImageUrl->AssetUrl)); //create the emblem image
$profile = new Imagick($profileAssetUrl); //create the profile image
$text = new ImagickDraw(); //create the text object
$textWeapon = new ImagickDraw(); //create the weapon text object

//set up the settings for our text
$text->setFillColor('white');
$text->setFont('fonts/baksheeshregular-webfont.ttf');
$text->setFontSize(12);

//set up the settings for our weapon text
$textWeapon->setFillColor('white');
$textWeapon->setFont('fonts/baksheeshregular-webfont.ttf');
$textWeapon->setFontSize(24);

//scale down the image by 55%
$weaponImageSize = $weapon->getImageGeometry();
$weaponScaleW = $weaponImageSize['width'] * 0.55;
$weaponScaleH = $weaponImageSize['height'] * 0.55;

$weapon->scaleImage($weaponScaleW, $weaponScaleH);

//scale down the image by 30%
$emblemImageSize = $emblem->getImageGeometry();
$emblemScaleW = $emblemImageSize['width'] * 0.30;
$emblemScaleH = $emblemImageSize['height'] * 0.30;

$emblem->scaleImage($emblemScaleW, $emblemScaleH);

//scale down the image by 70%
$profileImageSize = $profile->getImageGeometry();
$profileScaleW = $profileImageSize['width'] * 0.70;
$profileScaleH = $profileImageSize['height'] * 0.70;

$profile->scaleImage($profileScaleW, $profileScaleH);

//put all the images together
$bg->compositeImage( $profile, imagick::COMPOSITE_OVER, -10, -40 );

//get the text we need to display from the XML
$totalKillsText = $halo->GameModes->GameMode[2]->TotalKills;
$totalDeathsText = $halo->GameModes->GameMode[2]->TotalDeaths;
$killDeathRatioText = $halo->GameModes->GameMode[2]->KDRatio;
$favoriteWeaponText = $halo->FavoriteWeaponName;

//get the font metrics so we can no the width of the line of text
$favoriteWeaponMetrics = $bg->queryFontMetrics($textWeapon, $favoriteWeaponText);
$spartanMetrics = $bg->queryFontMetrics($textWeapon, rawurldecode($spartan));
$weaponChoiceMetrics = $bg->queryFontMetrics($text, 'WEAPON OF CHOICE');

//add our text to the image
$bg->annotateImage($text, 373, 19, 0, 'TOTAL KILLS');
$bg->annotateImage($text, 471, 19, 0, $totalKillsText);
$bg->annotateImage($text, 373, 33, 0, 'TOTAL DEATHS');
$bg->annotateImage($text, 471, 33, 0, $totalDeathsText);
$bg->annotateImage($text, 374, 47, 0, 'KD RATIO');
$bg->annotateImage($text, 471, 47, 0, $killDeathRatioText);
$bg->annotateImage($text, 374, 73, 0, 'WEAPON OF CHOICE');
//this sets the weapon text to the very right so we can prevent overflow
$bg->annotateImage($textWeapon, (500 - $favoriteWeaponMetrics['textWidth']) - 10, 95, 0, $favoriteWeaponText);
$bg->annotateImage($textWeapon, (250 - ($spartanMetrics['textWidth'] / 2)), 35, 0, rawurldecode($spartan));
$bg->annotateImage($text, 0, 10, 0, "halo4tags.com");

//put the emblem image next to the spartan text
$bg->compositeImage( $emblem, imagick::COMPOSITE_OVER, (250 - ($spartanMetrics['textWidth'] / 2)) - 40, 10 );

//put the weapon image next to the weapon text
if ($weaponChoiceMetrics['textWidth'] <= $favoriteWeaponMetrics['textWidth'])
	$bg->compositeImage( $weapon, imagick::COMPOSITE_OVER, ( 500 - $favoriteWeaponMetrics['textWidth'] - $weaponScaleW - 30 ), 60 );
else
	$bg->compositeImage( $weapon, imagick::COMPOSITE_OVER, ( 500 - $weaponChoiceMetrics['textWidth'] - $weaponScaleW - 30 ), 60 );

//set our header to display an image
header('Content-Type: image/png');

//print the image
echo $bg;
?>