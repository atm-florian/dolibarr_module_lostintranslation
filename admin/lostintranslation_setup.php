<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/lostintranslation.php
 * 	\ingroup	lostintranslation
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formadmin.class.php";
require_once '../lib/lostintranslation.lib.php';
require_once '../class/lostintranslation.class.php';

// Translations
$langs->load('main');
$langs->load('admin');
$langs->load('lostintranslation@lostintranslation');

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$word = GETPOST('word', 'alpha');
$langtosearch = GETPOST('langtosearch', 'alpha');
if(empty($langtosearch)) $langtosearch = $user->conf->MAIN_LANG_DEFAULT;
if(empty($langtosearch)) $langtosearch = $conf->global->MAIN_LANG_DEFAULT;

// Init LostInTranslationObject
$lit = new LostInTranslation($langtosearch);

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if(!empty($word)) {
	$lit->searchWordInLangFiles($word);
}

if($action == 'save_translation') {
	$langfile = GETPOST('langfile', 'alpha');
	$key = GETPOST('key', 'alpha');
	$newTranslation = GETPOST('newtranslation', 'alpha');
	$lit->saveNewTranslation($langfile, $key, $newTranslation);

	if(empty($lit->error)) setEventMessages($langs->trans('NewTranslationSaved'), array());
}

/*
 * View
 */
$page_name = "LostInTranslationSetup";
llxHeader('', $langs->trans($page_name),'','',0,0,array('/lostintranslation/js/lostintranslation.js'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = lostintranslationAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104855Name"),
    0,
    "lostintranslation@lostintranslation"
);

// If customlangs folder is not writeable, module can't be used
if(!$lit->isFolderWriteable()) {
	echo $langs->trans('CustomFolderNotWriteable');
} else {

	// Setup page goes here
	$form=new Form($db);
	$formadmin=new FormAdmin($db);
	$var=false;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

	$stats = '';
	if(!empty($lit)) {
		$stats = '<br>';
		$stats.= '&nbsp;&nbsp;'. picto_from_langcode($lit->lang);
		$stats.= ' '.$langs->trans('NbTerms').' : ' . $lit->nbTerms . '<br>';
		$stats.= '&nbsp;&nbsp;'. picto_from_langcode($lit->lang);
		$stats.= ' '.$langs->trans('NbCustomTerms').' : ' . $lit->nbTrans . '<br>';
	}

	// Select lang to customize
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("SelectLangToCustomize").$stats.'</td>';
	print '<td align="center">&nbsp;</td>';
	print '<td align="right">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="search_word">';
	print '<input type="text" name="word" value="'.$word.'">';
	print $formadmin->select_language($langtosearch,'langtosearch');
	print '<input type="submit" class="button" value="'.$langs->trans("Search").'">';
	print '</form>';
	print '</td></tr>';

	print '</table>';


	if(!empty($lit->searchRes)) {
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("File").'</td>'."\n";
		print '<td>'.$langs->trans("TranslationKey").'</td>'."\n";
		print '<td>'.$langs->trans("CurrentTranslation").'</td>'."\n";
		print '<td>'.$langs->trans("CustomizeTranslation").'</td>'."\n";
		print '<td>&nbsp;</td>'."\n";
		print '</tr>';

		foreach($lit->searchRes as $langfile => $trads) {
			foreach($trads as $key => $val) {
				$inputkey = 'TTrans['.$langfile.']['.$key.']';
				$input = '<form method="POST">';
				$input.= '<input type="hidden" name="action" value="save_translation" />';
				$input.= '<input type="hidden" name="langtosearch" value="'.$langtosearch.'" />';
				$input.= '<input type="hidden" name="word" value="'.$word.'" />';
				$input.= '<input type="hidden" name="langfile" value="'.$langfile.'" />';
				$input.= '<input type="hidden" name="key" value="'.$key.'" />';
				$input.= '<textarea  rows="4" cols="50" name="newtranslation" class="flat">'.$val['current'].'</textarea>';
				$input.= '<input type="image" src="'.img_picto('', 'save.png@lostintranslation', '', false, 1).'" style="vertical-align: middle;" />';
				$input.= '</form>';
				//$btsave = '<a href="#" class="saveTranslation">'.img_picto($langs->trans('Save'), 'save.png@lostintranslation').'</a>';

				print '<tr>';
				print '<td>'.$langfile.'</td>'."\n";
				print '<td>'.$key.'</td>'."\n";
				print '<td>'.$val['current'].'</td>'."\n";
				print '<td>'.$input.'</td>'."\n";
				//print '<td>'.$btsave.'</td>'."\n";
				print '</tr>';
			}
		}
	}

	//echo '<pre>';
	//print_r($lit->searchRes);
}

llxFooter();

$db->close();