<?php
/**
 * @package		Joomla.Plugin.System.Presenzecsvimport
 * @subpackage	System
 * @license		GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemPresenzecsvimport extends JPlugin
{
    function onAfterDispatch()
    {
        $app = JFactory::getApplication();

        // Esegui solo nel backend
        if ($app->isAdmin()) {
            $task = JRequest::getVar('task');

            if ($task == 'process_csv_import') {
                $csvFilePath = $this->params->get('csv_file_path');
                $tableName = $this->params->get('table_name');
                $this->importCsv($csvFilePath, $tableName);
                $app->redirect('index.php?option=com_content', 'Importazione CSV completata.', 'message'); // Reindirizza alla gestione articoli con un messaggio
            }

            // Aggiungi una voce di menu (opzionale)
            $menu = JSite::getMenu();
            if ($menu->getActive() && $menu->getActive()->component == 'com_content') {
                JToolBarHelper::custom('process_csv_import', 'upload', 'upload', 'Importa Presenze CSV', false);
            }
        }
    }

    function importCsv($csvFilePath, $tableName)
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);

        if (($handle = fopen($csvFilePath, "r")) !== FALSE) {
            // Salta la prima riga se contiene le intestazioni
            fgetcsv($handle, 1000, ",");

            while (($data = fgetcsv($handle, 1000, " ")) !== FALSE) {
                if (count($data) == 3) {
                    $matricola = $db->escape($data[0]);
                    $data_valida = $db->escape($data[1]);
                    $ora_valida = $db->escape($data[2]);

                    $query
                        ->insert($tableName)
                        ->columns('matricola, data, ora')
                        ->values("'$matricola', '$data_valida', '$ora_valida'");

                    $db->setQuery($query);
                    $db->query();

                    // Resetta la query per la prossima riga
                    $query->clear();
                }
            }
            fclose($handle);
        } else {
            JError::raiseWarning(100, 'Impossibile aprire il file CSV: ' . $csvFilePath);
        }
    }
}
