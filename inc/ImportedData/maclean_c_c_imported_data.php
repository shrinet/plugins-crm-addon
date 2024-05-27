<?php

namespace MacleanCustomCode\ImportedData;

use MacleanCustomCode\MacleanCustomCode;

if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

class FileParameters
{
    public $file          = null;
    public $file_path     = null;
    public $column_name   = null;
    public $column_name_2 = null;
    public $enclosure     = null;
    public $delimiter     = null;
    public $table         = null;

    public function __construct(
        int $file = 0,
        string $file_path = '',
        string $column_name = '',
        string $column_name_2 = '',
        string $delimiter = '',
        string $enclosure = '',
        string $table = ''
    ) {
        $this->file          = $file;
        $this->file_path     = $file_path;
        $this->column_name   = $column_name;
        $this->column_name_2 = $column_name_2;
        $this->delimiter     = $delimiter;
        $this->enclosure     = $enclosure;
        $this->table         = $table;
    }
}

class FileParametersFactory
{
    private const FILE          = 'file';
    private const COLUMN_NAME   = 'column';
    private const COLUMN_NAME_2 = 'column_2';
    private const DELIMITER     = 'delimiter';
    private const ENCLOSURE     = 'enclosure';
    private const TABLE         = 'table';

    public const FILE_MPSCUSTS    = 0;
    public const FILE_MPSITEM1    = 1;
    public const FILE_MPSITEM2    = 2;
    public const FILE_MPSITEMS    = 3;
    public const FILE_MPSORD      = 4;
    public const FILE_MPSQTCMT    = 5;
    public const FILE_MPSQTHDR    = 6;
    public const FILE_MPSQUOTE    = 7;
    public const FILE_MPSCROSSREF = 8;
    public const FILE_MSIORD      = 9;
    public const FILE_MSIITEM     = 10;

    public const PARAMETER_MAPPINGS = array(
        'mpscusts' => array(
            self::FILE          => self::FILE_MPSCUSTS,
            self::COLUMN_NAME   => '',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => ',',
            self::ENCLOSURE     => '"',
            self::TABLE         => ''
        ),

        'mpsitem1' => array(
            self::FILE          => self::FILE_MPSITEM1,
            self::COLUMN_NAME   => 'catalogno',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => '|',
            self::ENCLOSURE     => '`',
            self::TABLE         => 'mp_item_1'
        ),

        'mpsitem2' => array(
            self::FILE          => self::FILE_MPSITEM2,
            self::COLUMN_NAME   => 'catalogno',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => '|',
            self::ENCLOSURE     => '`',
            self::TABLE         => 'mp_item_2'
        ),

        'mpsitems' => array(
            self::FILE          => self::FILE_MPSITEMS,
            self::COLUMN_NAME   => 'catalogno',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => '|',
            self::ENCLOSURE     => '`',
            self::TABLE         => 'mp_item'
        ),

        'mpsord' => array(
            self::FILE          => self::FILE_MPSORD,
            self::COLUMN_NAME   => 'catalogno',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => '|',
            self::ENCLOSURE     => '',
            self::TABLE         => 'mp_order'
        ),

        'mpsqtcmt' => array(
            self::FILE          => self::FILE_MPSQTCMT,
            self::COLUMN_NAME   => '',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => '|',
            self::ENCLOSURE     => '`',
            self::TABLE         => 'mp_quote_comment'
        ),

        'mpsqthdr' => array(
            self::FILE          => self::FILE_MPSQTHDR,
            self::COLUMN_NAME   => '',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => '|',
            self::ENCLOSURE     => '`',
            self::TABLE         => 'mp_quote_header'
        ),

        'mpsquote' => array(
            self::FILE          => self::FILE_MPSQUOTE,
            self::COLUMN_NAME   => 'catalogno',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => '|',
            self::ENCLOSURE     => '`',
            self::TABLE         => 'mp_quote'
        ),

        'mpsxref' => array(
            self::FILE          => self::FILE_MPSCROSSREF,
            self::COLUMN_NAME   => 'mpscatalognumber',
            self::COLUMN_NAME_2 => 'partnumber',
            self::DELIMITER     => ',',
            self::ENCLOSURE     => '"',
            self::TABLE         => 'mp_cross_reference'
        ),

        'msiord' => array(
            self::FILE          => self::FILE_MSIORD,
            self::COLUMN_NAME   => 'catalogno',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => '|',
            self::ENCLOSURE     => '`',
            self::TABLE         => 'mp_order'
        ),

        'msiitem' => array(
            self::FILE          => self::FILE_MSIITEM,
            self::COLUMN_NAME   => 'catalogno',
            self::COLUMN_NAME_2 => '',
            self::DELIMITER     => '|',
            self::ENCLOSURE     => '`',
            self::TABLE         => 'mp_item_1'
        )
    );

    public static function from_file_path( string $file_path )
    {
        $file_path = strtolower( $file_path );
        foreach ( array_keys( self::PARAMETER_MAPPINGS ) as $key ) {
            if ( strpos( $file_path, $key ) !== false && array_key_exists( $key, self::PARAMETER_MAPPINGS ) ) {
                $paramValues = self::PARAMETER_MAPPINGS[ $key ];
                return new FileParameters(
                    $paramValues[ self::FILE ],
                    $file_path,
                    $paramValues[ self::COLUMN_NAME ],
                    $paramValues[ self::COLUMN_NAME_2 ],
                    $paramValues[ self::DELIMITER ],
                    $paramValues[ self::ENCLOSURE ],
                    $paramValues[ self::TABLE ]
                );
            }
        }

        return null;
    }
}

class Logger
{
    protected $file_path;
    protected $file_max_size;

    protected $file;
    protected $params;

    protected $warning_flag = false;
    protected $error_flag   = false;

    protected $options = array(
        'dateFormat' => 'd-M-Y H:i:s'
    );

    public function __construct( $file_path = 'error.txt', $file_max_size = 1000, $params = array() )
    {
        $this->file_path     = $file_path;
        $this->file_max_size = $file_max_size;

        // file max size must be positive
        if ( $this->file_max_size < 0 ) {
            $this->file_max_size = -$this->file_max_size;
        }

        $this->params = array_merge( $this->options, $params );

        if ( ! file_exists( $file_path ) ) {
            fopen( $file_path, 'w' );
        }
    }

    public function info( $message )
    {
        $this->writeLog( $message, 'INFO' );
    }

    public function debug( $message )
    {
        $this->writeLog( $message, 'DEBUG' );
    }

    public function warning( $message )
    {
        $this->warning_flag = true;
        $this->writeLog( $message, 'WARNING' );
    }

    public function error( $message )
    {
        $this->error_flag = true;
        $this->writeLog( $message, 'ERROR' );
    }

    public function writeLog( $message, $severity )
    {
        // open log file
        if ( ! is_resource( $this->file ) ) {
            $this->openLog();
        }

        $time = date( $this->params[ 'dateFormat' ] );
        fwrite( $this->file, "[$time] : [$severity] - $message" . PHP_EOL );
    }

    private function openLog()
    {
        $openFile = $this->file_path;
        // 'a' option = place pointer at end of file
        $this->file = fopen( $openFile, 'a' ) or exit( "Can't open $openFile!" );
    }

    public function __destruct()
    {
        if ( $this->file ) {
            fclose( $this->file );
        }
    }

    public function was_warning()
    {
        return $this->warning_flag;
    }

    public function was_error()
    {
        return $this->error_flag;
    }

    public function get_file_path(): string
    {
        return $this->file_path;
    }

}

class MacleanCCImportedData
{
    /** @var string */
    private const ACTION_NAME_NIGHTLY_IMPORT = 'maclean_nightly_ftp_import';

    /** @var string */
    private const OPTION_NAME_NIGHTLY_IMPORT = 'maclean_nightly_import_options';

    /** @var string */
    private const OPTION_KEY_PENDING_CLEANUP = 'pending_cleanup';

    /** @var string */
    private const OPTION_KEY_WAS_ERROR = 'was_error';

    /** @var string */
    private const OPTION_KEY_START_MESSAGE_SENT = 'start_message_sent';

    /** @var int Number of seconds the importer is allowed to execute for. */
    private const MAX_EXECUTION_TIME = 30;

    /** @var string Directory to move processed files to. */
    private const PROCESSED_DIRECTORY = 'previous-import';

    /** @var string Directory containing table creation sql. */
    private const TABLE_STRUCTURE_DIRECTORY = 'mp_tables_sql';

    /** @var string Status to assign to staged db records. */
    private const RECORD_STATUS_STAGED = 'staged';

    /** @var string Status to assign to old db records. */
    private const RECORD_STATUS_OLD = 'old';

    /** @var string File name within import directory to log to. */
    private const LOG_FILE_NAME = 'import-log.txt';

    /** @var int Max length of the log file in characters. */
    private const LOG_FILE_MAX_LENGTH_CHARS = 500000;

    /** @var string Lock file to implement php flock functionality to prevent simultaneous imports. */
    private const LOCK_FILE_NAME = 'import.lock';

    /** @var int Number of records that will be inserted in a single SQL transaction. */
    private const INSERT_CHUNK_SIZE = 10000;

    /** @var string Subject of error email. */
    private const EMAIL_SUBJECT_ERROR = 'MacLean FTP Import Warning / Error: ';

    /** @var string Subject of success email. */
    private const EMAIL_SUBJECT_SUCCESS = 'MacLean FTP Import Sequence Has Ended: ';

    /** @var string Subject of start email. */
    private const EMAIL_SUBJECT_START = 'MacLean FTP Import Initiated: ';

    /** @var string Body of error email. */
    private const EMAIL_BODY_ERROR = 'While performing an automated import, an error or warning occurred. Please see the attached log file.';

    /** @var string Body of import start email. */
    private const EMAIL_BODY_START = 'An automated import has begun. You will receive a follow-up email when the import has completed or if any errors occur.';

    /** @var string Body of success email. */
    private const EMAIL_BODY_SUCCESS = 'The FTP Import processed successfully.';

    /** @var array Headers for error email. */
    private const EMAIL_HEADERS = array( 'Content-Type: text/html', 'charset: UTF-8','From: MacLean Power Importer <noreply@macleanpower.com>' );

    /** @var MacleanCustomCode */
    private $master = null;

    /** @var Logger */
    private $logger = null;

    /** @var int */
    private $time_start = 0;

    /** @var int Number of records processed during this run. */
    private $record_count = 0;

    /** @var string Path to the file being processed currently. */
    private $file_path = '';

    /** @var string Name of the file being processed currently. */
    private $file_name = '';

    /** @var FileParameters Parameters for the file being processed currently. */
    private $file_params = null;

    /** @var string "INSERT INTO $table ( $cols ) VALUES " so we can append values from array after aggregating. */
    private $insert_sql_prefix = '';

    /** @var array Holds SQL VALUES clauses to append to insert_sql_prefix. */
    private $value_sql_array = array();

    /** @var array Tracks missing record keys from a csv file to prevent duplicate logs. */
    private $record_keys_missing = array();

    /** @var array Tracks unexpected record keys from a csv file to prevent duplicate logs. */
    private $record_keys_unexpected = array();

    /** @var array Stores the table schema for the current file. */
    private $table_schema = array();

    private $directory_path;

    /**
     * MacleanCCImportedData constructor.
     * @param MacleanCustomCode $master
     */
    public function __construct( MacleanCustomCode $master = NULL)
    {
        $this->master = $master;

        // save start time
        $this->time_start = microtime( true );

        add_filter( 'cron_schedules', array( $this, 'add_cron_schedule_5min' ) );
        add_action( self::ACTION_NAME_NIGHTLY_IMPORT, array( $this, 'nightly_ftp_import' ) );
        add_action( 'maclean_send_ftp_emails', array( $this, 'send_emails_ftp_automated' )  );
       

        // schedule next import if necessary
        if ( $this->get_option_start_message_sent() === true ) // in the middle of an import, keep importing
            $this->nightly_ftp_import();
        else // schedule next import check
            if ( ! wp_next_scheduled( self::ACTION_NAME_NIGHTLY_IMPORT ) )
                wp_schedule_event( time(), 'hourly', self::ACTION_NAME_NIGHTLY_IMPORT );

       //schedule the ftp log email
        if ( !wp_next_scheduled( "maclean_send_ftp_emails" ) ) {
        wp_schedule_event( time() + 60*10, "5min", "maclean_send_ftp_emails" );
        }

    }


	public function set_master( $master ) {
        $this->master = $master;
        add_action( 'maclean_send_ftp_emails', array( $this, 'send_emails_ftp_automated' )  );
	} 

	public function send_emails_ftp_automated() {
        //get the most recent record in ftp_log table 
        global $wpdb;
        $log_email = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM mp_ftp_log ORDER BY log_timestamp DESC LIMIT 1" ) );
        $log_email_is_sent=  $log_email->log_email_issent;
        $log_email_status= $log_email->log_status;
        if($log_email_is_sent=="unsent"){      
		$this->master->background_process_email->add_to_queue( get_class( $this ), "send_emails_if_necessary",  $log_email_status);
        $this->master->background_process_email->send_queue();
        }
        
	}

    public function send_emails_if_necessary( $task_item ) {
      
       //check if unsend and send what email
         if ( $task_item[ "value" ] != "success")
            $this->send_email(
                self::EMAIL_SUBJECT_ERROR . home_url(),
                //self::EMAIL_BODY_ERROR . "<br/><a href='" . home_url( '/wp-content/uploads/nightly-import/import-log.txt' ) . "'>Import Log</a>",
                self::EMAIL_BODY_ERROR,
                array()
            );
        else
            $this->send_email(
                self::EMAIL_SUBJECT_SUCCESS . home_url(),
                //self::EMAIL_BODY_SUCCESS . "<br/><a href='" . home_url( '/wp-content/uploads/nightly-import/import-log.txt' ) . "'>Import Log</a>",
                self::EMAIL_BODY_SUCCESS,
                array()
            );
        global $wpdb;
        $wpdb->query( "UPDATE mp_ftp_log SET log_email_issent = 'sent' WHERE log_email_issent = 'unsent'");

    }



    public function add_cron_schedule_5min( $schedules )
    {
        if ( ! isset( $schedules[ '5min' ] ) )
            $schedules[ '5min' ] = array(
                'interval' => 5 * 60,
                'display'  => __( 'Once every 5 minutes' )
            );
        return $schedules;
    }

    public function get_file_time( $path ) {
        $x = get_option( 'maclean_nightly_import_log_file_time' ) or 0;
        return $x;
    }

    public function check_truncate_log_file() {
        $path = $this->directory_path . '/log/' . date("Ymd") . ".import-log.txt";
        $days = get_field( "import_log_days_to_keep", "option" );
        if ( $days === "" || $days === false || $days === null || empty( $days ) ) {
            $days = "2";
        }

        $oldlog = $this->directory_path .'/log/'. date('Ymd',mktime(0,0,0,date("m"),(date("d") - ($days)),date("Y"))) . '.import-log.txt';

        if ( !file_exists( $path ) ) {
            touch( $path );
        }

        //if ( $this->get_file_time( $path ) < strtotime( date( "Y-m-d") .  " - " . $days . " days" ) ) {
        if (file_exists($oldlog)){
            unlink( $oldlog );
            //touch( $path );
            update_option( 'maclean_nightly_import_log_file_time', time() );
        }

        $log_files=glob($this->directory_path . '/log/*');
        $combine_log_file_paths  = preg_grep( '/\.txt$/i',  $log_files );
        $out = fopen($this->directory_path."/import-log.txt", "w");
        foreach( $log_files as $file){
            fwrite($out, file_get_contents($file));
        }
        fclose($out);

         

    }

    /**
     * Each time this function is called, it will find the next available import file and begin processing its contents
     * until its time budget expires. It uses wp_option values to track progress between runs, enabling it to resume
     * after timeouts. This was required because the import files are too large to process within wpengine's allotted
     * 60 seconds of execution time for a request.
     */
    public function nightly_ftp_import(): void
    {
        // read import directory contents
        $this->directory_path = wp_upload_dir()[ 'basedir' ] . "/nightly-import";

        // check for lock file
        $lock_file_path = $this->directory_path . '/' . self::LOCK_FILE_NAME;
        if ( !file_exists( $lock_file_path ) ) {
            touch( $lock_file_path );
            $this->check_truncate_log_file();
        } else {
            return;
        }

        // read file paths to available import files
        $directory_contents = glob( $this->directory_path . '/*' );
        $import_file_paths  = preg_grep( '/\.csv$/i', $directory_contents );

        // send start / finish emails
        if ( count( $import_file_paths ) === 0 ) {
            // reset for next import
            if ( $this->get_option_start_message_sent() ) {
                $this->update_option_start_message_sent( false );
                global $wpdb;

                //purge open orders
                $wpdb->query( "DELETE o1.* FROM `mp_order` as o1 INNER JOIN `mp_order` as o2 ON o1.pono = o2.pono AND o1.id <> o2.id AND o1.s <> o2.s AND o1.catalogno = o2.catalogno AND o1.ordernum = o2.ordernum WHERE o1.s <> 'C'" );

                //clean up dup orders
                $wpdb->query( "DELETE o1.* FROM `mp_order` as o1 INNER JOIN `mp_order` as o2 ON o1.pono = o2.pono AND o1.id < o2.id AND o1.item = o2.item AND o1.ordernum = o2.ordernum AND o1.catalogno = o2.catalogno and o1.stockno = o2.stockno" );

                if ( file_exists( $this->directory_path . '/order.lock' ) ) {
                    unlink( $this->directory_path . '/order.lock' );
                }
                if ( file_exists( $this->directory_path . '/' . self::LOCK_FILE_NAME ) ) {
                    unlink( $this->directory_path . '/' . self::LOCK_FILE_NAME );
                }
                if ( file_exists( $this->directory_path . '/item.lock' ) ) {
                    unlink( $this->directory_path . '/item.lock' );
                }
                $this->update_option_pending_cleanup( true );
                $this->update_database_statuses();
            }
        } else {
            $import_file_paths_bns = array_map( function ( $e ) {
                return basename( $e );
            }, $import_file_paths );

            if ( count( array_filter( $import_file_paths_bns, function ( $e ) {
                    return strpos( strtolower($e), "mpsord" ) !== false || strpos( strtolower($e), "msiord" ) !== false;
                } ) ) > 0 && ! file_exists( $this->directory_path . '/order.lock' ) ) {

                foreach ( $import_file_paths as $file_path_1 ) {

                    //purge open order mpsord00
                    if( strpos( strtolower(basename($file_path_1)), "mpsord00" ) !== false){
                        global $wpdb;
                        $wpdb->query( "DELETE FROM `mp_order` WHERE s='O' and item<>''" ); 
                    }
                    //purge open order msiord00
                    if( strpos( strtolower(basename($file_path_1)), "msiord00" ) !== false){
                        global $wpdb;
                        $wpdb->query( "DELETE FROM `mp_order` WHERE s='O' and item=''" ); 
                    }

                    rename( $file_path_1, str_replace( basename( $file_path_1 ), strtolower( basename( $file_path_1 ) ), $file_path_1 ) );
                }

                $directory_contents = glob( $this->directory_path . '/*' );
                $import_file_paths  = preg_grep( '/\.csv$/i', $directory_contents );

                $import_file_paths_processed = preg_grep( '/(mps|msi)ord.+\.csv/i', glob( $this->directory_path . "/previous-import/*" ) );

                touch( $this->directory_path . '/order.lock' );

                $import_file_paths_bns = array_map( function ( $e ) {
                    return basename( $e );
                }, $import_file_paths );

                $import_file_paths_to_move = array_filter( $import_file_paths_processed, function ( $e ) use ( $import_file_paths_bns ) {
                    return ! in_array( basename( $e ), $import_file_paths_bns ) && (strpos( $e, "mpsord" ) !== false || strpos( $e, "msiord" ) !== false);
                } );

                foreach ( $import_file_paths_to_move as $ftm ) {
                    rename( $ftm, str_replace( "/previous-import", "", $ftm ) );
                }

                $directory_contents = glob( $this->directory_path . '/*' );

                // read file paths to available import files
                $import_file_paths = preg_grep( '/\.csv$/i', $directory_contents );
            }

            if ( count( array_filter( $import_file_paths_bns, function ( $e ) {
                return strpos( strtolower($e), "item" ) !== false;
            } ) ) > 0 && ! file_exists( $this->directory_path . '/item.lock' ) ) {

                foreach ( $import_file_paths as $file_path_1 ) {
                    rename( $file_path_1, str_replace( basename( $file_path_1 ), strtolower( basename( $file_path_1 ) ), $file_path_1 ) );
                }

                $directory_contents = glob( $this->directory_path . '/*' );
                $import_file_paths  = preg_grep( '/\.csv$/i', $directory_contents );

                $import_file_paths_processed = preg_grep( '/(.+)([item])(.+)\.csv/i', glob( $this->directory_path . "/previous-import/*" ) );

                touch( $this->directory_path . '/item.lock' );

                $import_file_paths_bns = array_map( function ( $e ) {
                    return basename( $e );
                }, $import_file_paths );

                $import_file_paths_to_move = array_filter( $import_file_paths_processed, function ( $e ) use ( $import_file_paths_bns ) {
                    return ! in_array( basename( $e ), $import_file_paths_bns ) && strpos( $e, "item" ) !== false;
                } );

                foreach ( $import_file_paths_to_move as $ftm ) {
                    rename( $ftm, str_replace( "/previous-import", "", $ftm ) );
                }

                $directory_contents = glob( $this->directory_path . '/*' );

                // read file paths to available import files
                $import_file_paths = preg_grep( '/\.csv$/i', $directory_contents );
            }

            // found files, but start message not sent yet
            if ( ! $this->get_option_start_message_sent() ) {
                $this->send_start_email();
                $this->update_option_start_message_sent( true );
            }
        }

        // create logger and point it at import directory
        $log_file_path = $this->directory_path . '/log/' . date("Ymd") . '.' . self::LOG_FILE_NAME;
        $this->logger  = new Logger( $log_file_path, self::LOG_FILE_MAX_LENGTH_CHARS );

        $should_log_elapsed_time = count( $import_file_paths ) > 0;

        $processed_file_paths = 0;
        // process next file if one exists
        while ( count( $import_file_paths ) > 0 && $this->get_elapsed_time() < self::MAX_EXECUTION_TIME ) {
            $this->file_path = array_pop( $import_file_paths );
            if ( !$this->is_valid_file() ) {
                unlink( $this->file_path );
                continue;
            }
            $this->process_file();
            // reset for next file
            $this->insert_sql_prefix = '';
            $this->record_count      = 0;
            $this->value_sql_array   = array();
            $processed_file_paths++;
        }

        // save error / warning status
        if ( $this->logger->was_warning() || $this->logger->was_error() ) {
            $this->update_option_was_error( true );
        }

        // report elapsed time
        if ( $should_log_elapsed_time ) {
            $time_elapsed = microtime( true ) - $this->time_start;
            $this->logger->info( "total time elapsed: $time_elapsed seconds" );
        }

        if ($this->get_option_pending_cleanup() || $this->get_option_was_error() || ( $processed_file_paths !== 0 && count( $import_file_paths ) === $processed_file_paths ) ) {
            $this->send_finish_email();
            $this->update_option_pending_cleanup( false );
            $this->update_option_was_error( false );
        }

        // delete the lock file
        if ( file_exists( $lock_file_path ) ) {
            unlink( $lock_file_path );
        }
    }

    private function is_valid_file() {
        $keys = array_keys(FileParametersFactory::PARAMETER_MAPPINGS);
        foreach ( $keys as $key ) {
            if ( strpos( $this->file_path, $key ) !== false ) {
                return true;
            }
        }
        return false;
    }

    private function process_file(): void
    {
        $file_start_time = microtime( true );

        $this->logger->info( 'processing file: ' . print_r( $this->file_path, true ) );

        $this->file_path = $this->move_file_to_lowercase_path_if_necessary( $this->file_path );

        // parse file information, lowercase path if necessary
        $this->file_name   = pathinfo( $this->file_path )[ 'basename' ];
        $this->file_params = FileParametersFactory::from_file_path( $this->file_path );

        if ( ! $this->file_params ) {
            $this->logger->warning( "Unexpected file: no parsing parameters set for file name: $this->file_name" );
            $this->logger->warning( "Moving file to processed directory: $this->file_name" );
            $this->process_file_final_actions( false );
            return;
        }

        $this->update_table_schema();

        // read the file contents into array of lines
        $file_lines      = $this->read_file_lines();
        $file_line_count = count( $file_lines );

        // determine file line count, must be at least 2 ( header + 1 or more data lines )
        $line_count = count( $file_lines );
        if ( $line_count < 2 ) {
            $this->logger->warning( "Import file must have at least 2 file_lines: $this->file_name" );
            $this->process_file_final_actions();
            return;
        }

        // process header line
        $header_line = trim( $file_lines[ 0 ] );
        // if last header field ends in delimiter, remove the delimiter
        if ( $header_line[ strlen( $header_line ) - 1 ] === $this->file_params->delimiter && $header_line[ strlen( $header_line ) - 2 ] !== $this->file_params->delimiter ) {
            $header_line = substr( $header_line, 0, -1 );
        }

        // process header data
        $header = str_getcsv( $header_line, $this->file_params->delimiter, $this->file_params->enclosure );
        $header = $this->normalize_headers( $header );

        // determine processing start index, either use the value from the db or 1 to skip header
        $index = $this->get_option_file_line_number( $this->file_name ) ? : 1;
        $start = $index;

        // process all lines into records
        while ( $this->get_elapsed_time() < self::MAX_EXECUTION_TIME && $index < $file_line_count ) {
            // we reached EOF, all files end in empty line
            if ( $index === $file_line_count - 1 ) {
                $index++;
                break;
            }

            // parse the line into an array
            $line = trim( $file_lines[ $index ] );
            $data = str_getcsv( $line, $this->file_params->delimiter, $this->file_params->enclosure );

            if ( count( $data ) !== count( $header ) ) {
                // file-specific line parsing
                switch ( $this->file_params->file ) {
                    case FileParametersFactory::FILE_MPSCUSTS:
                    case FileParametersFactory::FILE_MPSORD:
                    case FileParametersFactory::FILE_MPSQTHDR:
                    case FileParametersFactory::FILE_MPSQUOTE:
                        break; // no file-specific processing required

                    case FileParametersFactory::FILE_MPSITEM1:
                    case FileParametersFactory::FILE_MPSITEM2:
                    case FileParametersFactory::FILE_MSIITEM:
                    case FileParametersFactory::FILE_MPSITEMS:
                        // lines that have a trailing delimiter, must be removed
                        $line = $this->remove_trailing_delimiter_from_line( $line, $this->file_params->delimiter );
                        $data = str_getcsv( $line, $this->file_params->delimiter, $this->file_params->enclosure );
                        break;

                    case FileParametersFactory::FILE_MPSQTCMT:
                        if ( count( $data ) === 5 && $data[ 3 ] === '' ) {
                            unset( $data[ 3 ] );
                            $data = array_values( $data );
                        }
                        break;

                    case FileParametersFactory::FILE_MPSCROSSREF:
                        // if the data and header are different lengths, keep including more lines until they match

                        while ( count( $data ) < count( $header ) && $index < $file_line_count - 1 ) {
                            $index++;
                            $line .= "\n" . trim( $file_lines[ $index ] );
                            $data = str_getcsv( $line, $this->file_params->delimiter, $this->file_params->enclosure );
                        }

                        // if we have too many items, slice the data to match header
                        if ( count( $data ) > count( $header ) ) {
                            $data = array_slice( $data, 0, count( $header ) );
                        }

                        break;
                }
            } else {
                switch ( $this->file_params->file ) {
                    case FileParametersFactory::FILE_MPSCROSSREF:
                        // if the data and header are different lengths, keep including more lines until they match
                        $hasUnmatchedEnclosure = substr_count( $line, $this->file_params->enclosure ) % 2 !== 0;
                        while ( $hasUnmatchedEnclosure && $index < $file_line_count - 1 ) {
                            $index++;
                            $line                  .= "\n" . trim( $file_lines[ $index ] );
                            $data                  = str_getcsv( $line, $this->file_params->delimiter, $this->file_params->enclosure );
                            $hasUnmatchedEnclosure = substr_count( $line, $this->file_params->enclosure ) % 2 !== 0;
                        }

                        // if we have too many items, slice the data to match header
                        if ( count( $data ) > count( $header ) ) {
                            $data = array_slice( $data, 0, count( $header ) );
                        }

                        break;
                }
            }

            if ( count( $data ) === count( $header ) ) {
                $record = array_combine( $header, $data );

                if ( $this->file_params->file === FileParametersFactory::FILE_MPSCUSTS ) {
                    $this->process_record_customer( $record );
                } else {
                    $this->process_record( $record );
                }

                $this->record_count++;
            } else {
                $this->logger->warning( "Invalid data at line: $index" );
            }

            $index++;
        }

        if ( $this->file_params->file === FileParametersFactory::FILE_MPSCUSTS ) {
            // customer insert reporting
            $elapsed_time = microtime( true ) - $file_start_time;
            $this->logger->info( "processed $this->record_count record(s) in $elapsed_time seconds" );
            $this->logger->info( "processed line range: $start - $index" );
        } else {
            // non-customer processing and reporting
            if ( $this->insert_sql_prefix !== '' ) {
                global $wpdb;

                // check that table exists, if not, create it
                $table = $this->file_params->table;
                if ( $wpdb->query( "SELECT 1 FROM $table LIMIT 1" ) === false ) {
                    $this->create_table_from_sql_file( $table );
                    $this->logger->warning( "created table: $table" );
                }

                // run query with insert_sql
                $insert_count          = 0;
                $this->value_sql_array = array_chunk( $this->value_sql_array, self::INSERT_CHUNK_SIZE );

                foreach ( $this->value_sql_array as $value_set ) {
                    $wpdb->query( 'START TRANSACTION' );
                    $chunk_insert_count = $wpdb->query( $this->insert_sql_prefix . implode( ',', $value_set ) );
                    $wpdb->query( 'COMMIT' );

                    if ( $wpdb->last_error !== '' ) {
                        $this->logger->warning( 'sql error during record insert' );
                        $this->logger->warning( $wpdb->last_error );
                        $this->logger->warning( htmlspecialchars( $wpdb->last_query, ENT_QUOTES ) );
                    } else {
                        $insert_count += $chunk_insert_count;
                    }
                }

                if ( $insert_count !== $this->record_count ) {
                    $this->logger->warning( "insert_count !== record_count" );
                    $this->logger->warning( "insert_count: $insert_count" );
                    $this->logger->warning( "record_count: $this->record_count" );
                }

                $elapsed_time = microtime( true ) - $file_start_time;
                $this->logger->info( "processed $this->record_count record(s) in $elapsed_time seconds" );
                $this->logger->info( "processed line range: $start - $index" );
            }
        }

        // clean up
        if ( $index >= $file_line_count ) {
            $this->process_file_final_actions();
        } else {
            $this->update_option_file_line_number( $this->file_name, $index );
        }
    }

    private function update_database_statuses(): void
    {
        global $wpdb;

        $file_names = array_values( array_filter(
            array_keys( $this->get_importer_options() ),
            function ( $element ) {
                return strpos( $element, '.csv' ) !== false;
            }
        ) );

        foreach ( $file_names as $file_name ) {
            $file_params = FileParametersFactory::from_file_path( $file_name );

            $table = $file_params->table;
            if ( $table === '' ) {
                continue;
            }

            // toggle statuses
            $staged_status = self::RECORD_STATUS_STAGED;

            $staged_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = '$staged_status'" );
            if ( $staged_count !== null && intval( $staged_count ) > 0 ) {
                if ( $table !== 'mp_order' || ! file_exists( $this->directory_path . '/order.lock' ) ) {
                    $wpdb->query( "DELETE FROM $table WHERE ( status is null or status='' )" );
                }

                $wpdb->query( "UPDATE $table SET status = '' WHERE status = '$staged_status'" );
            }
        }
    }

    private function send_start_email(): void
    {
        $this->send_email(
            self::EMAIL_SUBJECT_START . home_url(),
            self::EMAIL_BODY_START
        );
    }

    private function send_finish_email(): void
    {
        global $wpdb;
        $table ='mp_ftp_log';
        $data = array('log_status' => $this->get_option_was_error() ? 'warning/error':'success',
                      'log_timestamp' => date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) ), 
                      'log_email_issent' =>'unsent');
        $wpdb->insert($table,$data);
      
    }

    private function send_email(
        string $subject,
        string $body = '',
        array $attachments = array()
    ): void {
        if ( ( $email_recipients_option = get_field( 'ftp_email_recipients', 'option' ) ) !== false ) {
            $recipients = str_getcsv( $email_recipients_option );
            foreach ( $recipients as $index => $email ) {
                if ( ! is_email( $email ) ) {
                    unset( $recipients[ $index ] );
                }
            }

            if ( count( $recipients ) > 0 ) {
                $subject = date( 'Y-m-d H:i:s' ) . ' ' . $subject;
                wp_mail(
                    implode( ',', $recipients ),
                    $subject,
                    $body,
                    self::EMAIL_HEADERS,
                    array()
                );
            }
        }
    }

    private function process_file_final_actions( $track_in_db = true )
    {
        $this->file_path = $this->move_file_to_processed_directory( $this->file_path );

        if ( $track_in_db === true ) {
            $this->update_option_file_line_number( $this->file_name, 0 );
        }
    }

    private function process_record( array &$record ): void
    {
        // remove elements with empty key
        foreach ( $record as $key => $value ) {
            if ( $key === '' ) {
                unset( $record[ $key ] );
            }
        }

        // make lowercase and remove spaces from keys
        $record = array_combine(
            array_map( function ( $key ) {
                return str_replace( ' ', '', strtolower( $key ) );
            }, array_keys( $record ) ),
            array_values( $record )
        );

        // SQL escape for single quotes
        foreach ( $record as $key => $value ) {
            $record[ $key ] = str_replace( "'", "''", $value );
        }

        // set status
        $record[ 'status' ] = self::RECORD_STATUS_STAGED;

        // set cleaned catalog number
        switch ( $this->file_params->file ) {
            case FileParametersFactory::FILE_MPSORD:
            case FileParametersFactory::FILE_MSIORD:
                $record[ 'file_from' ] = basename( $this->file_params->file_path );
                $record[ 'cleaned_catalog_no' ] =
                    $this->master->functions->clean_data_strict( $record[ $this->file_params->column_name ] );
                break;
            case FileParametersFactory::FILE_MPSCROSSREF:
                $record[ 'cleaned_part_no' ]    =
                    $this->master->functions->clean_data_strict( $record[ $this->file_params->column_name_2 ] );
                $record[ 'cleaned_catalog_no' ] =
                    $this->master->functions->clean_data_strict( $record[ $this->file_params->column_name ] );
                break;
            case FileParametersFactory::FILE_MSIITEM:
            case FileParametersFactory::FILE_MPSITEM1:
            case FileParametersFactory::FILE_MPSITEM2:
            case FileParametersFactory::FILE_MPSITEMS:
            case FileParametersFactory::FILE_MSIITEM:
            case FileParametersFactory::FILE_MPSQUOTE:
                $record[ 'cleaned_catalog_no' ] =
                    $this->master->functions->clean_data_strict( $record[ $this->file_params->column_name ] );
                break;
        }

        // make sure record matches database schema, exclude non-matching elements
        $record = $this->map_record_to_schema( $record );

        // initial insert sql, for table name and header columns
        $table = $this->file_params->table;
        if ( $this->insert_sql_prefix === '' ) {
            $headers                 = '`' . implode( '`,`', array_keys( $record ) ) . '`';
            $this->insert_sql_prefix = "INSERT INTO $table ( $headers ) VALUES ";
        }

        // save values sql to array for insert later
        $values = "'" . implode( "','", array_values( $record ) ) . "'";
        array_push( $this->value_sql_array, "( $values )" );
    }

    private function update_table_schema(): void
    {
        global $wpdb;
        $this->table_schema = $wpdb->get_col( "DESCRIBE {$this->file_params->table}", 0 );
        if ( $this->table_schema === false ) {
            $this->table_schema = array();
        }

        // disregard 'id' column be removing from schema array
        if ( ( $index = array_search( 'id', $this->table_schema ) ) !== false ) {
            unset( $this->table_schema[ $index ] );
        }
    }

    private function map_record_to_schema( array $record ): array
    {
        // remove unexpected fields from record
        foreach ( $record as $key => $value ) {
            if ( ! in_array( $key, $this->table_schema ) ) {
                unset( $record[ $key ] );

                // log error if not already logged
                if ( ! array_key_exists( $this->file_params->file, $this->record_keys_unexpected ) ) {
                    $this->record_keys_unexpected[ $this->file_params->file ] = array();
                } else {
                    if ( ! array_key_exists( $key, $this->record_keys_unexpected[ $this->file_params->file ] ) ) {
                        $this->record_keys_unexpected[ $this->file_params->file ][ $key ] = true;
                        $this->logger->warning( "unexpected field in record: $key" );
                    }
                }
            }
        }

        // assign empty values to expected fields that are not present
        foreach ( $this->table_schema as $key ) {
            if ( ! array_key_exists( $key, $record ) ) {
                $record[ $key ] = '';

                if ( ! array_key_exists( $this->file_params->file, $this->record_keys_missing ) ) {
                    $this->record_keys_missing[ $this->file_params->file ] = array();
                } else {
                    if ( ! array_key_exists( $key, $this->record_keys_missing[ $this->file_params->file ] ) ) {
                        $this->record_keys_missing[ $this->file_params->file ][ $key ] = true;
                        $this->logger->warning( "missing expected field in record: $key" );
                    }
                }
            }
        }

        return $record;
    }

    private function process_record_customer( array $record ): void
    {
        global $wpdb;

        $account_number = $record[ 'cust' ];
        $sql            = "SELECT post_id FROM " . $wpdb->prefix . "postmeta WHERE meta_key = 'cust' AND meta_value = '$account_number'";
        $post_id        = $wpdb->get_results( $sql );

        if ( ! empty( $post_id ) && property_exists( $post_id[ 0 ], 'post_id' ) ) {
            update_field( 'customername', $record[ 'customername' ], $post_id[ 0 ]->post_id );
            update_field( 'fact', $record[ 'fact' ], $post_id[ 0 ]->post_id );
        }

        $this->record_count++;
    }

    private function get_option_pending_cleanup(): bool
    {
        $importer_options = $this->get_importer_options();

        if ( array_key_exists( ( $key = self::OPTION_KEY_PENDING_CLEANUP ), $importer_options ) ) {
            return boolval( $importer_options[ $key ] );
        } else {
            return false;
        }
    }

    private function update_option_pending_cleanup( bool $pending_cleanup ): void
    {
        $options = $this->get_importer_options();

        $options[ self::OPTION_KEY_PENDING_CLEANUP ] = $pending_cleanup;

        $this->update_importer_options( $options );
    }

    private function update_option_was_error( bool $was_error ): void
    {
        $options = $this->get_importer_options();

        $options[ self::OPTION_KEY_WAS_ERROR ] = $was_error;

        $this->update_importer_options( $options );
    }

    private function update_option_start_message_sent( bool $start_message_sent ): void
    {
        $options = $this->get_importer_options();

        $options[ self::OPTION_KEY_START_MESSAGE_SENT ] = $start_message_sent;

        $this->update_importer_options( $options );
    }

    private function get_option_file_line_number( string $file_name ): int
    {
        $importer_options = $this->get_importer_options();

        if ( array_key_exists( $file_name, $importer_options ) ) {
            return $importer_options[ $file_name ];
        } else {
            return false;
        }
    }

    private function get_option_was_error(): bool
    {
        $options = $this->get_importer_options();

        if ( array_key_exists( ( $key = self::OPTION_KEY_WAS_ERROR ), $options ) ) {
            return boolval( $options[ $key ] );
        } else {
            return false;
        }
    }

    private function get_option_start_message_sent(): bool
    {
        $options = $this->get_importer_options();

        if ( array_key_exists( ( $key = self::OPTION_KEY_START_MESSAGE_SENT ), $options ) ) {
            return boolval( $options[ $key ] );
        } else {
            return false;
        }
    }

    private function get_importer_options(): array
    {
        return get_option( self::OPTION_NAME_NIGHTLY_IMPORT, array() );
    }

    private function update_importer_options( array $options ): void
    {
        update_option( self::OPTION_NAME_NIGHTLY_IMPORT, $options, 'no' );
    }

    private function update_option_file_line_number( string $file_name, int $file_line_number ): void
    {
        $options               = $this->get_importer_options();
        $options[ $file_name ] = $file_line_number;
        $this->update_importer_options( $options );
    }

    private function get_elapsed_time(): float
    {
        return microtime( true ) - $this->time_start;
    }

    private function create_table_from_sql_file( string $table ): void
    {
        global $wpdb;
        $sql_file_path = __DIR__ . '/' . self::TABLE_STRUCTURE_DIRECTORY . '/' . $table . '.sql';
        $sql           = file_get_contents( $sql_file_path );
        $queries       = explode( ';', $sql );

        foreach ( $queries as $query ) {
            $wpdb->query( $query );
        }
    }

    private function remove_trailing_delimiter_from_line( string $line, string $delimiter ): string
    {
        if ( $line[ strlen( $line ) - 1 ] === $delimiter ) {
            return substr( $line, 0, -1 );
        } else {
            return $line;
        }
    }

    /**
     * Copies the file at the provided path to the same path with only lowercase, and then
     * deletes the original file if the copy was successful.
     *
     * @param string $file_path
     * @return string
     */
    private function move_file_to_lowercase_path_if_necessary( string $file_path ): string
    {
        $path_info = pathinfo( $file_path );

        $dir_name  = $path_info[ 'dirname' ];
        $base_name = $path_info[ 'basename' ];

        // make all file paths lowercase for standardization
        $lowercase_file_path = $dir_name . '/' . strtolower( $base_name );
        if ( $file_path !== $lowercase_file_path ) {
            rename( $file_path, $lowercase_file_path );
        }
        return $lowercase_file_path;
    }

    private function move_file_to_processed_directory( string $file_path ): string
    {
        $path_info = pathinfo( $file_path );

        $dir_name  = $path_info[ 'dirname' ];
        $base_name = $path_info[ 'basename' ];

        $processed_dir_name = $dir_name . '/' . self::PROCESSED_DIRECTORY;
        if ( ! file_exists( $processed_dir_name ) ) {
            mkdir( $processed_dir_name );
        }

        $new_file_path = $processed_dir_name . '/' . $base_name;

        rename( $file_path, $new_file_path );

        return $new_file_path;
    }

    private function read_file_lines(): array
    {
        $lines = explode( "\r\n", utf8_encode( $this->master->functions->clean_bom( file_get_contents( $this->file_path ) ) ) );
        if ( count( $lines ) >= 2 ) {
            return $lines;
        }
        $lines = explode( "\n", utf8_encode( $this->master->functions->clean_bom( file_get_contents( $this->file_path ) ) ) );
        if ( count( $lines ) >= 2 ) {
            return $lines;
        }
        $lines = explode( "\r", utf8_encode( $this->master->functions->clean_bom( file_get_contents( $this->file_path ) ) ) );
        if ( count( $lines ) >= 2 ) {
            return $lines;
        }
        return array();
    }

    public function normalize_headers( $headers )
    {
        return array_map( function ( $e ) {
            return preg_replace( array( "/\*/", "/\//", "/\#/", "/\ /" ), array( "astrsk", "fwdslsh", "num", "" ), strtolower( trim( $this->master->functions->clean_data( $e ) ) ) );
        }, $headers );
    }
}