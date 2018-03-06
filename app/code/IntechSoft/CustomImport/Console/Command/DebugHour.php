<?php

namespace IntechSoft\CustomImport\Console\Command;

use Symfony\Component\Console\Command\Command; // for parent class
use Symfony\Component\Console\Input\InputInterface; // for InputInterface used in execute method
use Symfony\Component\Console\Output\OutputInterface; // for OutputInterface used in execute method
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use IntechSoft\CustomImport\Model\Import;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\DateTime;
use IntechSoft\CustomImport\Helper\UrlRegenerate;
use Magento\Framework\Registry;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;

class DebugHour extends Command
{
    const CUSTOM_IMPORT_FOLDER = 'import/cron/hour';
    const SUCCESS_MESSAGE      = 'Import finished successfully from file - ';
    const FAIL_MESSAGE         = 'Import fail from file - ';

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
     * @var Import
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
    protected $_coreRegistry;
    protected $_transportBuilder;
    protected $_scopeConfig;

    /**
     * Import constructor.
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploader
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \IntechSoft\CustomImport\Model\Import $importModel
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \IntechSoft\CustomImport\Helper\UrlRegenerate $urlRegenerate
     */
    public function __construct(
        UploaderFactory $uploader,
        Filesystem $filesystem,
        Import $importModel,
        DirectoryList $directoryList,
        DateTime $date,
        UrlRegenerate $urlRegenerate,
        Registry $coreRegistry,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig

    ) {
        $this->_uploader            = $uploader;
        $this->_filesystem          = $filesystem;
        $this->_importModel         = $importModel;
        $this->_directoryList       = $directoryList;
        $this->_date                = $date;
        $this->_coreRegistry        = $coreRegistry;
        $this->_urlRegenerateHelper = $urlRegenerate;
        $this->_transportBuilder    = $transportBuilder;
        $this->_scopeConfig         = $scopeConfig;
        //$this->_logger = $logger;

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/hour_cron.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
        parent::__construct();
    }


    protected function configure()
    {
        $this->setName('debug:hour')
            ->setDescription('Debug the Hour script');

        parent::configure();
    }

    /**
     * Method executed when cron runs in server
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_coreRegistry->register('isIntechsoftCustomImportModule', 1);
       // mail('xxx@example.com', 'My Subject', "message");
        $templateId = $this->_scopeConfig->getValue("customImportSection/emailGroup/template_id", ScopeInterface::SCOPE_STORE);
        $senderDataId = $this->_scopeConfig->getValue("customImportSection/emailGroup/sender_data_id", ScopeInterface::SCOPE_STORE);

       /* $vars = array();
        $tomail = 'kaplunovskymv@gmail.com';
        $toname = 'Maks';

        if ($templateId && $senderDataId) {
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => Area::AREA_ADMINHTML, 'store' => Store::DEFAULT_STORE_ID])
                ->setTemplateVars($vars)
                ->setFrom($senderDataId)
                ->addTo($tomail, $toname)
                ->getTransport();
            $transport->sendMessage();
        }*/
        /*$templateId = $this->getConfig('template');
        $identity = $this->getConfig('identity');
        $storeid = 1;
        $vars = array();
        $tomail = 'testing@testing.com';
        $toname = 'testing testing';

        if ($templateId && $identity) {
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeid])
                ->setTemplateVars($vars)
                ->setFrom($identity)
                ->addTo($tomail, $toname)
                ->getTransport();
            $transport->sendMessage();
        }*/

        ini_set('memory_limit', '2048M');

        $this->_logger->info('hourly cron started at - ' . $this->_date->gmtDate('Y-m-d H:i:s'));
        $importDir = $this->_directoryList->getPath(DirectoryList::VAR_DIR) . '/' . self::CUSTOM_IMPORT_FOLDER ;
        $this->_logger->info('$importDir - '.$importDir);

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
            /* @var $importModel \IntechSoft\CustomImport\Model\Import */
//            $importModel = $this->_importModel->create();
            $importModel = $this->_importModel;
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
                $this->_urlRegenerateHelper->regenerateUrl();
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
                $vars = array("failed_origin_name" => $file, "failed_full_name" => $archiveName);
                $vars = new \Magento\Framework\DataObject($vars);
                $tomail = 'kaplunovskymv@gmail.com';
                $toname = 'Maks';

                if ($templateId && $senderDataId) {
                    $transport = $this->_transportBuilder
                        ->setTemplateIdentifier($templateId)
                        ->setTemplateOptions(['area' => Area::AREA_ADMINHTML, 'store' => Store::DEFAULT_STORE_ID])
                        ->setTemplateVars($vars->getData())
                        ->setFrom($senderDataId)
                        ->addTo($tomail, $toname)
                        ->getTransport();
                   // $transport->sendMessage();
                }
            }


//            if (count($importModel->errors) == 0) {
//                $this->_logger->info(self::SUCCESS_MESSAGE . $file);
//
//                /*** Moved to import History***/
//                $src = $importedFileName;
//                $archiveName = "completed_" . date('YmdHis') . "_" . $file;
//                $dest = $this->_directoryList->getPath(DirectoryList::VAR_DIR) . "/import_history/" . $archiveName;
//                $r = rename($src, $dest);
//                if ($r) {
//                    $this->_logger->info('Moved to import history');
//                }
//                /*** Moved to import History***/
//
//                //unlink($importDir. '/' .$file);
//                $this->_urlRegenerateHelper->regenerateUrl();
//            } else {
//                foreach ($importModel->errors as $error) {
//                    if (is_array($error)) {
//                        $error = implode(' - ', $error);
//                    }
//                    $this->_logger->info($error);
//                }
//            }

            if ($i <= 1) {
                //  break;
            }
        }
        $this->_logger->info('hourly cron finished at - ' . $this->_date->gmtDate('Y-m-d H:i:s'));
    }
}