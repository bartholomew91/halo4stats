<?php
class Halo
{
	private $spartan;
	private $weaponAsset;
	private $profileAsset;
	private $emblemAsset;
	private $haloXML;
	private $bg;
	private $weapon;
	private $emblem;
	private $text;
	private $bitText;
	private $totalKills;
	private $totalDeaths;
	private $killDeathRatio;
	private $favoriteWeapon;
	private $metrics;
	private $weaponScale;
	private $emblemScale;
	private $profileScale;

	/**
	* Setup
	**/
	public function __construct($spartan)
	{
		$this->spartan = $spartan;

		//setup our asset URLs
		$this->weaponAsset = "https://assets.halowaypoint.com/games/h4/damage-types/v1/";
		$this->profileAsset = "https://spartans.svc.halowaypoint.com/players/".rawurlencode($spartan)."/h4/spartans/fullbody?target=large";
		$this->emblemAsset = "https://emblems.svc.halowaypoint.com/h4/emblems/";

		//get the spartans service record
		$file = file_get_contents("https://stats.svc.halowaypoint.com/en-us/players/".rawurlencode($spartan)."/h4/servicerecord");

		//parse our XML
		$this->haloXML = new SimpleXMLElement($file);

		//setup the text we need to display
		$this->totalKills = $this->haloXML->GameModes->GameMode[2]->TotalKills;
		$this->totalDeaths = $this->haloXML->GameModes->GameMode[2]->TotalDeaths;
		$this->killDeathRatio = $this->haloXML->GameModes->GameMode[2]->KDRatio;
		$this->favoriteWeapon = $this->haloXML->FavoriteWeaponName;

		//perform necessary functions to create the image
		$this->_ImagickInit();
		$this->_scale();
		$this->_metrics();
		$this->_annotate();
		$this->_composite();
	}

	/**
	* Initialize everything for Imagick
	**/
	private function _ImagickInit()
	{
		$this->bg = new Imagick("images/backgrounds/halo_bg.png"); //create the background image
		$this->weapon = new Imagick($this->weaponAsset . str_replace('{size}', 'large', $this->haloXML->FavoriteWeaponImageUrl->AssetUrl)); //create the weapon image
		$this->emblem = new Imagick($this->emblemAsset . str_replace('{size}', '120', $this->haloXML->EmblemImageUrl->AssetUrl)); //create the emblem image
		$this->profile = new Imagick($this->profileAsset); //create the profile image
		$this->text = new ImagickDraw(); //create the text object
		$this->bigText = new ImagickDraw(); //create the weapon text object

		//set up the settings for our text
		$this->text->setFillColor('white');
		$this->text->setFont('fonts/Quicksand.otf');
		$this->text->setFontSize(10);

		//set up the settings for our weapon text
		$this->bigText->setFillColor('white');
		$this->bigText->setFont('fonts/Quicksand.otf');
		$this->bigText->setFontSize(22);
	}

	/**
	* Scale down our images
	**/
	private function _scale()
	{
		//scale down the image by 55%
		$weaponImageSize = $this->weapon->getImageGeometry();
		$this->weaponScale['w'] = $weaponImageSize['width'] * 0.55;
		$this->weaponScale['h'] = $weaponImageSize['height'] * 0.55;

		$this->weapon->scaleImage($this->weaponScale['w'], $this->weaponScale['h']);

		//scale down the image by 30%
		$emblemImageSize = $this->emblem->getImageGeometry();
		$this->emblemScale['w'] = $emblemImageSize['width'] * 0.30;
		$this->emblemScale['h'] = $emblemImageSize['height'] * 0.30;

		$this->emblem->scaleImage($this->emblemScale['w'], $this->emblemScale['h']);

		//scale down the image by 70%
		$profileImageSize = $this->profile->getImageGeometry();
		$this->profileScale['w'] = $profileImageSize['width'] * 0.70;
		$this->profileScale['h'] = $profileImageSize['height'] * 0.70;

		$this->profile->scaleImage($this->profileScale['w'], $this->profileScale['h']);
	}

	/**
	* Get the font metrics for proper placement
	**/
	private function _metrics()
	{
		$this->metrics['favoriteWeapon'] = $this->bg->queryFontMetrics($this->bigText, $this->favoriteWeapon);
		$this->metrics['spartan'] = $this->bg->queryFontMetrics($this->bigText, rawurldecode($this->spartan));
		$this->metrics['weaponChoice'] = $this->bg->queryFontMetrics($this->text, 'WEAPON OF CHOICE');
	}

	/**
	* Annotates the image with text
	**/
	private function _annotate()
	{
		$this->bg->annotateImage( $this->text, 373, 19, 0, 'TOTAL KILLS');
		$this->bg->annotateImage( $this->text, 471, 19, 0, $this->totalKills);
		$this->bg->annotateImage( $this->text, 373, 33, 0, 'TOTAL DEATHS');
		$this->bg->annotateImage( $this->text, 471, 33, 0, $this->totalDeaths);
		$this->bg->annotateImage( $this->text, 374, 47, 0, 'KD RATIO');
		$this->bg->annotateImage( $this->text, 471, 47, 0, $this->killDeathRatio);
		$this->bg->annotateImage( $this->text, 374, 73, 0, 'WEAPON OF CHOICE');

		//this sets the weapon text to the very right so we can prevent overflow
		$this->bg->annotateImage( $this->bigText, (500 - $this->metrics['favoriteWeapon']['textWidth']) - 10, 95, 0, $this->favoriteWeapon);
		//put the spartans name in the middle of the image (horizontally)
		$this->bg->annotateImage( $this->bigText, (250 - ($this->metrics['spartan']['textWidth'] / 2)), 35, 0, rawurldecode($this->spartan));
	}

	/**
	* This will combine all images into one image
	**/
	private function _composite()
	{
		//composite the spartan profile image
		$this->bg->compositeImage( $this->profile, imagick::COMPOSITE_OVER, -10, -40 );

		//composite the emblem next to the spartans name
		$this->bg->compositeImage( $this->emblem, imagick::COMPOSITE_OVER, (250 - ($this->metrics['spartan']['textWidth'] / 2)) - 40, 10 );

		if ($this->metrics['weaponChoice']['textWidth'] <= $this->metrics['favoriteWeapon']['textWidth']) {
			//put the weapon image next to the weapon text
			$this->bg->compositeImage( $this->weapon, imagick::COMPOSITE_OVER, ( 500 - $this->metrics['favoriteWeapon']['textWidth'] - $this->weaponScale['w'] - 30 ), 60 );
		} else { 
			//put the image to the left of the weapon choice text, if the favorite weapon text is less than the weapon choice text
			$this->bg->compositeImage( $this->weapon, imagick::COMPOSITE_OVER, ( 500 - $this->metrics['weaponChoice']['textWidth'] - $this->weaponScale['w'] - 30 ), 60 );
		}
	}

	/**
	* Render the created image as PNG
	**/
	public function render()
	{
		//set our header to display an image
		header('Content-Type: image/png');

		//print the image
		echo $this->bg;
	}

	/**
	* Display the formatted XML for debugging purposes
	**/
	public function displayXML()
	{
		echo "<pre>";
		print_r($this->haloXML);
		echo "</pre>";
	}
}