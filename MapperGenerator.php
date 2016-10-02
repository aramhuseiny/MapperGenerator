<?php


class pdoConnection
{
    protected $servername = "localhost";
    protected $username = "root";
    protected $password = "";
    protected $dbName = "";

    protected $instance = null;

    public function __construct()
    {
        $this->instance =  new PDO("mysql:host=localhost;dbname=$this->dbName", $this->username, $this->password);
    }

    public  function _getInstance()
    {
        if( $this->instance === null )
        {
            $this->instance =  new pdoConnection();
        }
        return $this->instance;
    }

}

class Product  {


    public $connection = null;

    private $tables = array(); // array of tables name

    private $fields = array(); // array of fields name for a table

    private $dbName = null; // database name that you wat to load schema

    public function __construct( $dbName = null)
    {
        try {

            $this->connection = (new pdoConnection())->_getInstance();
            $this->dbName = $dbName; // set dbName
            $this->_DoAll(); // call do all function to create class mappers

        }
        catch(PDOException $e)
        {
            echo "Connection failed: " . $e->getMessage();
        }

    }

    /**********************************************************************/
    // load table names from database ,
    public function _SelectTableNames( ) // return table of the database
    {
        if ($this->dbName != null)
        {
            $sql = "SELECT DISTINCT table_name  FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '$this->dbName' ORDER BY table_name";
            //echo "table name: " . $sql;

            $res = $this->connection->query( $sql );

            foreach ( $res  as $value )

                array_push($this->tables ,  $value[0] ); // create array of table names

            unset( $res ); // free the res

            return $this->tables; // return the array from table names
        }
        else
            return null;



    }

    /**********************************************************************/
    //load field names from table by table name and dbname
    public function getColumnNames($table ){

        // sql query to loading fields for a table in the defined database
            $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table' and TABLE_SCHEMA = '$this->dbName'";
            //echo $sql;
            try {
                $core = $this->connection; //Core::getInstance();
                $stmt = $core->prepare($sql);
                $stmt->execute();
                $output = array();
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    $output[] = $row['COLUMN_NAME'];
                }
                return $output;
            }

            catch(PDOException $pe) {
                trigger_error('Could not connect to MySQL database. ' . $pe->getMessage() , E_USER_ERROR);
            }


    }
    /*********************************************************************/
    /********************** class for making mapper class ****************/
    function _CreateMapperClass( $table  , $fields )
    {
        $className = $table; // set the className equal to $table => classNAme is the name of our mapper class

        $FileName = ucfirst($className)."Mapper.php";

        $file = fopen($FileName ,"w");

        $content = "<?php\n";

        // write the namespace of this mapper class
        $content .= "namespace Model\Mapper; \n";

        // write class name
        $content .= "class ".$className."Mapper extends AbstractMapper\n{ \n\n";

        //write variables for the mapper => variables are same with table fields
        $content .= "\tprotected $"."_entityTable = '$table'; \n";

        $content .= "\tprotected $"."_entityClass = 'Model".ucfirst($table)."'; \n";
        
        $content .= "\tprotected $"."_entityData = null; \n";

        $content .= "\tprotected function _createEntity(array $"."data) \n";

        $content .= "\t{ \n";

        // create an array of variables
        //$content .= "\t\t $".$table." = new $"."this->_entityClass(array( \n";
        $content .= "\t\t $"."this->_entityData = array( \n";

        for ( $i = 0 ; $i < sizeof($fields) ; $i++ ) {

            $content .= "\t\t\t'".$fields[$i]."' => $"."data['".$fields[$i]."'] ";
            if( $i < ( sizeof( $fields ) -1  ) )
            {
                $content .= " , \n ";
            }
            else{
                $content .= "\n";
            }

        }



        //$content .= "\t\t ));\n";

        $content .= "\t\t );\n";

        //return the array to manipulate data
        $content .= "\t\t return $"."this->_entityData;\n";

        $content .= "\t}\n";


        // closing the php tag in class
        $content .= "}\n\n?>";


        fprintf($file,$content);
        try{

            // create the true permission for mapper class
            chmod($FileName , 0777 );
        }
        catch (  Exception $exp )
        {
            "Error on access File: ".$exp->getMessage();
        }
    }

    /************************************************************************/
    /************** Do All : this func do all task automatically*************/
    function _DoAll()
    {
        $tables = $this->_SelectTableNames( ); // load tables name


        // if the database have tables, load fields of each table
        if( $tables != null )
        {

            foreach ($tables as $tbl ) // do this acts on each table
            {
                // load fields of table to $fields
                $fields = $this->getColumnNames( $tbl );

                // create mapper class based on table name ( $tbl ) and it's fields ( $fields )
                $this->_CreateMapperClass( $tbl , $fields );

            }
        }

    }



}
/**************************************************************/


$dbName = "OdnoosUsers";
$ins = new Product( $dbName );

?> 