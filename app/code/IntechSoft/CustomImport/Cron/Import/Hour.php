<?php
namespace IntechSoft\CustomImport\Cron\Import;

use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use IntechSoft\CustomImport\Model\ImportFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\DateTime;
use IntechSoft\CustomImport\Helper\UrlRegenerate;
use Magento\Framework\Registry;
use IntechSoft\CustomImport\Model\Import as CustomImportModel;

class Hour
{
    const CUSTOM_IMPORT_FOLDER = 'import/cron/hour';
    const SUCCESS_MESSAGE      = 'Import finished successfully from file - ';
    const FAIL_MESSAGE         = 'Import fail from file - ';

    /**
     * @var Registry
     */
    private $_coreRegistry;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploader;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * @var \IntechSoft\CustomImport\Model\Import
     */
    protected $_importModel;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $_directoryList;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \IntechSoft\CustomImport\Helper\UrlRegenerate
     */
    protected $_urlRegenerateHelper;

    /**
     * Import Hour constructor.
     *
     * @param UploaderFactory $uploader
     * @param Filesystem      $filesystem
     * @param ImportFactory   $importModel
     * @param DirectoryList   $directoryList
     * @param Registry        $coreRegistry
     * @param DateTime        $date
     * @param UrlRegenerate   $urlRegenerate
     */
    public function __construct(
        UploaderFactory $uploader,
        Filesystem $filesystem,
        ImportFactory $importModel,
        DirectoryList $directoryList,
        Registry $coreRegistry,
        DateTime $date,
        UrlRegenerate $urlRegenerate
    ) {
        $this->_uploader            = $uploader;
        $this->_filesystem          = $filesystem;
        $this->_importModel         = $importModel;
        $this->_directoryList       = $directoryList;
        $this->_date                = $date;
        $this->_urlRegenerateHelper = $urlRegenerate;
        //$this->_logger = $logger;
        $this->_coreRegistry = $coreRegistry;

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/hour_cron.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
    }

    /**
     * Method executed when cron runs in server
     */
    public function execute()
    {
        ini_set('memory_limit', '2048M');

        $this->_logger->info('hourly cron started at - ' . $this->_date->gmtDate('Y-m-d H:i:s'));
        $importDir = $this->_directoryList->getPath(DirectoryList::VAR_DIR) . '/' . self::CUSTOM_IMPORT_FOLDER ;
        $this->_logger->info('$importDir - '.$importDir);

        $this->_coreRegistry->register(CustomImportModel::REGISTER_KEY, 1);

        if(!is_dir($importDir)) {
            mkdir($importDir, 0775);
        }

        $fileList = scandir($importDir);

        $i = 0;
        foreach ($fileList as $file) {
            if ($file == '.' || $file == '..' || $file == 'dump') {
                continue;
            }
            $i++;

            $this->_coreRegistry->unregister('importSuccessFlag');
            $importedFileName = $importDir . '/' . $file;
            $this->_logger->info('$importedFileName - ' . $importedFileName);
            $importModel = $this->_importModel->create();
            $importModel->setCsvFile($importedFileName, true)->process();

            $importSuccessFlag = $this->_coreRegistry->registry('importSuccessFlag');
            if ($importSuccessFlag === 1) {
                $this->_logger->info(self::SUCCESS_MESSAGE . $file);

                /*** Moved to import History***/
                $src = $importedFileName;
                $archiveName = "completed_" . date('YmdHis') . "_" . $file;
                $dest = $this->_directoryList->getPath(DirectoryList::VAR_DIR) . "/import_history/" . $archiveName;
                $r = rename($src, $dest);
                if ($r) {
                    $this->_logger->info('Moved to import history');
                }
                /*** Moved to import History***/

                //unlink($importDir. '/' .$file);

                // TODO: for what is it?
//                $this->_urlRegenerateHelper->regenerateUrl();
            } else {
                foreach ($importModel->errors as $error) {
                    if (is_array($error)) {
                        $error = implode(' - ', $error);
                    }
                    $this->_logger->info($error);
                }
                $src = $importedFileName;
                $archiveName = "failed_" . date('YmdHis') . "_" . $file;
                $dest = $this->_directoryList->getPath(DirectoryList::VAR_DIR) . "/failed_import_history/" . $archiveName;
                if (!is_dir(str_replace($archiveName, "", $dest))) {
                    mkdir(str_replace($archiveName, "", $dest));
                }
                $r = rename($src, $dest);
                if ($r) {
                    $this->_logger->info('Moved to failed history');
                }

//                $vars = array("failed_origin_name" => $file, "failed_full_name" => $archiveName);
//                $vars = new \Magento\Framework\DataObject($vars);
//                $tomail = 'kaplunovskymv@gmail.com';
//                $toname = 'Maks';

//                if ($templateId && $senderDataId) {
//                    $transport = $this->_transportBuilder
//                        ->setTemplateIdentifier($templateId)
//                        ->setTemplateOptions(['area' => Area::AREA_ADMINHTML, 'store' => Store::DEFAULT_STORE_ID])
//                        ->setTemplateVars($vars->getData())
//                        ->setFrom($senderDataId)
//                        ->addTo($tomail, $toname)
//                        ->getTransport();
//                    $transport->sendMessage();
//                }
            }

        }
        $this->_logger->info('hourly cron finished at - ' . $this->_date->gmtDate('Y-m-d H:i:s'));
    }
}