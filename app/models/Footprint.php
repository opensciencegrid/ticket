<?

require_once("app/httpspost.php");

class Footprint
{
    public function __construct()
    {
        $this->values = array();
        
        $this->values["PROJECTNUM"] = "71";
        $this->values["PROJECTNAME"] = "Open Science Grid";
        $this->values["TO"] = "osg@tick-indy.globalnoc.iu.edu";
        $this->values["FROM"] = "hayashis@indiana.edu";
        $this->values["<i>STATUS</i>"] = "Engineering";
        $this->values["Customer+Impact"] = "4";
    }

    public function setTitle($v) { $this->values["TITLE"] = $v; } //ticket title
    public function setFirstName($v) { $this->values["First__bName"] = $v; }
    public function setLastName($v) { $this->values["Last__bName"] = $v; }
    public function setOfficePhone($v) { $this->values["Office__bPhone"] = $v; }
    public function setEmail($v) { $this->values["Email__baddress"] = $v; }
    public function setDescription($v) { 
        dlog($v);
        $this->values["LONGDESCRIPTION"] = $v; 
    }

    public function setVO($v) { 
        $name = $this->lookupFootprintVOName($v);
        $this->values["Originating__bVO__bSupport__bCenter"] = $name; 

        //TODO - remove this when lookup is implemented
        $this->values["Submitter VO ID"] = $v; 
    }

    public function setVORequested($v) { 
        $name = $this->lookupFootprintVOName($v);
        $this->values["__vo_requested"] = $name;

        //TODO - remove this when lookup is implemented
        $this->values["__vo_requested"] = $v; 
    }

    public function setResourceWithIssue($resource_id) { 
        $rs_model = new ResourceSite();
        $resource = $rs_model->fetch($resource_id);
        $this->values["Resource where user is having this issue"] = $resource->resource_name;
        $this->values["Support Center where the issue resource is supported"] = $resource->sc_name;
        $fp_sc_name = $this->lookupFootprintVOName($resource->sc_id);
        $this->values["Destination__bVO__bSupport__bCenter"] = $fp_sc_name;

        //find primary resource admin
        $prac_model = new PrimaryResourceAdminContact();
        $prac = $prac_model->fetch($resource_id);
        $this->values["Primary Admin Email Address"] = $prac->primary_email;
    }

    private function lookupFootprintVOName($id)
    {
        //TODO..
        return "OSG-GOC";
    }

    private function lookoupFootprintResourceName($id)
    {
        //TODO - convert vo_id on OIM to Footprint VO name
        return "OSG-GOC";
    }


/*
<i>Status</i>   Engineering
Access__bInformation    
Activity        Change
Announcements__bSent__Q Yes
Attack__bType   Host+Compromise
CLLI    
Cell__bPhone    812-606-7104
City    
Comments        
Country 
Customer+Impact 4
DATE_TYPE       0
Day_Acceptance__bDate__b__PUTC__p       
Day_Activity__bDate__b__PUTC__p 
Day_ENG__bNext__bAction__bDate__fTime__b__PUTC__p       
Day_Earliest__bRequested__bDate__b__PUTC__p     09
Day_Latest__bRequested__bDate__b__PUTC__p       10
Day_Production__bDate__b__PUTC__p       
Day_SD__bNext__bAction__bDate__fTime__b__PUTC__p        
Day_Scheduled__bFSR__bEnd__bTime__b__PUTC__p    
Day_Scheduled__bFSR__bStart__bTime__b__PUTC__p  
Day_When__bwas__bPart__bReceived__Q__b__PUTC__p 
Day_When__bwas__bRMA__bGenerated__Q__b__PUTC__p 
Day___Gi__gActual__bEnd__bTime__b__PUTC__p__G__fi__g    
Day___Gi__gActual__bStart__bTime__b__PUTC__p__G__fi__g  
Day___Gi__gScheduled__bEnd__bTime__b__PUTC__p__G__fi__g 10
Day___Gi__gScheduled__bStart__bTime__b__PUTC__p__G__fi__g       09
Destination__bTicket__bNumber   
Destination__bVO__bSupport__bCenter     OSG-GOC
ENG__bNext__bAction__bItem      
Email__baddress hayashis@indiana.edu
Emergency__bMaintenance No
FROM    hayashis@indiana.edu
Facility__bOwner        
First__bName    Soichi
Hands__band__bEyes__bProvider   
Hour_ENG__bNext__bAction__bDate__fTime__b__PUTC__p      
Hour_Earliest__bRequested__bDate__b__PUTC__p    1
Hour_Latest__bRequested__bDate__b__PUTC__p      0
Hour_SD__bNext__bAction__bDate__fTime__b__PUTC__p       
Hour_Scheduled__bFSR__bEnd__bTime__b__PUTC__p   
Hour_Scheduled__bFSR__bStart__bTime__b__PUTC__p 
Hour_When__bwas__bPart__bReceived__Q__b__PUTC__p        
Hour_When__bwas__bRMA__bGenerated__Q__b__PUTC__p        
Hour___Gi__gActual__bEnd__bTime__b__PUTC__p__G__fi__g   
Hour___Gi__gActual__bStart__bTime__b__PUTC__p__G__fi__g 
Hour___Gi__gScheduled__bEnd__bTime__b__PUTC__p__G__fi__g        2
Hour___Gi__gScheduled__bStart__bTime__b__PUTC__p__G__fi__g      3
Hours   0
IM__bHandle     
Infrastructure__bAttack__Q      No
Inventory__bUpdated__Q  No
Is__bthis__bNetwork__bImpacting__Q      No
Job__bTitle     
LONGDESCRIPTION here+is+my+description.
Last__bName     Hayashi
Maintenance__bType      Software
Manned__bPOP__b__P1__CYes__p    
Maps__b__7__bDocuments__bModified__Q    No
Minute_ENG__bNext__bAction__bDate__fTime__b__PUTC__p    
Minute_Earliest__bRequested__bDate__b__PUTC__p  3
Minute_Latest__bRequested__bDate__b__PUTC__p    5
Minute_SD__bNext__bAction__bDate__fTime__b__PUTC__p     
Minute_Scheduled__bFSR__bEnd__bTime__b__PUTC__p 
Minute_Scheduled__bFSR__bStart__bTime__b__PUTC__p       
Minute_When__bwas__bPart__bReceived__Q__b__PUTC__p      
Minute_When__bwas__bRMA__bGenerated__Q__b__PUTC__p      
Minute___Gi__gActual__bEnd__bTime__b__PUTC__p__G__fi__g 
Minute___Gi__gActual__bStart__bTime__b__PUTC__p__G__fi__g       
Minute___Gi__gScheduled__bEnd__bTime__b__PUTC__p__G__fi__g      5
Minute___Gi__gScheduled__bStart__bTime__b__PUTC__p__G__fi__g    3
Minutes 0
Monitoring__bTools__bUpdated__Q No
Month_Acceptance__bDate__b__PUTC__p     
Month_Activity__bDate__b__PUTC__p       
Month_ENG__bNext__bAction__bDate__fTime__b__PUTC__p     
Month_Earliest__bRequested__bDate__b__PUTC__p   09
Month_Latest__bRequested__bDate__b__PUTC__p     09
Month_Production__bDate__b__PUTC__p     
Month_SD__bNext__bAction__bDate__fTime__b__PUTC__p      
Month_Scheduled__bFSR__bEnd__bTime__b__PUTC__p  
Month_Scheduled__bFSR__bStart__bTime__b__PUTC__p        
Month_When__bwas__bPart__bReceived__Q__b__PUTC__p       
Month_When__bwas__bRMA__bGenerated__Q__b__PUTC__p       
Month___Gi__gActual__bEnd__bTime__b__PUTC__p__G__fi__g  
Month___Gi__gActual__bStart__bTime__b__PUTC__p__G__fi__g        
Month___Gi__gScheduled__bEnd__bTime__b__PUTC__p__G__fi__g       09
Month___Gi__gScheduled__bStart__bTime__b__PUTC__p__G__fi__g     09
Network__bAdministration__bNotified__bof__bApplicable__bDates__Q        No
Network__bImpact        2-High
Office__bPhone  999-999-9999
Organization    
Originating__bTicket__bNumber   
Originating__bVO__bSupport__bCenter     OSG-GOC
Outage__bType   Undetermined
PROJECTNAME     Open+Science+Grid
PROJECTNUM      71
Part__bDescription      
Part__bModel__b__3      
Part__bSerial__b__3__b__Pnew__p 
Part__bSerial__b__3__b__Pold__p 
RFO     what+is+RFO?
RMA__b__3       11111
Ready__bto__bClose__Q   No
Requested__bTime__bFrame        Anytime
SD__bNext__bAction__bItem       
Send__bwhich__bdescription__bto__bassignees__Q  No+Choice
Shipper__bFROM__bSite   
Shipper__bTO__bSite     
Shipping__bAddress__b__Pif__bdifferent__p       
Site__bAddress  
Site__bDescription      what+site?
Source__bof__bImpact    Customer
State   
Suite   
Summary__bof__bCurrent__bStatus__bUpdated__Q    No
TITLE   test+ticket.+Please+assign+this+to+Soichi+Hayashi
TO      hayashis@indiana.edu
Ticket__uType   Problem/Request
Tracking__b__3__bFROM__bSite    
Tracking__b__3__bTO__bSite      
Type    Customer+Connection
Type__bof__bFSR Receive
Vendor__b1      No+Choice
Vendor__b1__bCase__b__3 
Vendor__b2      No+Choice
Vendor__b2__bCase__b__3 
Vendor__b3      
Vendor__b3__bCase__b__3 
Vendor__bInvolvement    No
Year_Acceptance__bDate__b__PUTC__p      
Year_Activity__bDate__b__PUTC__p        
Year_ENG__bNext__bAction__bDate__fTime__b__PUTC__p      
Year_Earliest__bRequested__bDate__b__PUTC__p    2008
Year_Latest__bRequested__bDate__b__PUTC__p      2008
Year_Production__bDate__b__PUTC__p      
Year_SD__bNext__bAction__bDate__fTime__b__PUTC__p       
Year_Scheduled__bFSR__bEnd__bTime__b__PUTC__p   
Year_Scheduled__bFSR__bStart__bTime__b__PUTC__p 
Year_When__bwas__bPart__bReceived__Q__b__PUTC__p        
Year_When__bwas__bRMA__bGenerated__Q__b__PUTC__p        
Year___Gi__gActual__bEnd__bTime__b__PUTC__p__G__fi__g   
Year___Gi__gActual__bStart__bTime__b__PUTC__p__G__fi__g 
Year___Gi__gScheduled__bEnd__bTime__b__PUTC__p__G__fi__g        2008
Year___Gi__gScheduled__bStart__bTime__b__PUTC__p__G__fi__g      2008
Zip     
__Gi__gAffected__G__fi__g       
__Gi__gSummary__bof__bCurrent__bStatus__G__fi__g
*/

    public function submit()
    {
        $ret = https_post("tick.globalnoc.iu.edu", "/MRcgi/MRProcessIncomingForms.pl", $this->values);

        //TODO.. analyze $ret
        return true;
    }
}

/*
use strict;
use SOAP::Lite;
my $USE_PROXY_SERVER = 1;
my $soap = new SOAP::Lite;
$soap->uri( 'http://fakeserver.phoneycompany.com:2021/MRWebServices' );
if( $USE_PROXY_SERVER )
{
    $soap->proxy(
        'http://fakeserver.phoneycompany.com:2021/MRcgi/MRWebServices.pl', 
        proxy => ['http' => 'http://localhost:8888/'] );
}
else
{
    $soap->proxy( 'http://fakeserver.phoneycompany.com:2021/MRcgi/MRWebServices.pl' );
}
my $soapenv = $soap->MRWebServices__createIssue(
    'WebServices',
    'fakepassword',
    '',
    {
        projectID => 78,
        title => 'Place title of new issue here.',
        assignees => ['user1', 'user2'],
        priorityNumber => 1,
        status => 'Open',
        description => "Place issue description here.\nFrom PERL code.",
        abfields =>
        {
            Last__bName => 'Doe',
            First__bName => 'John',
            Email__baddress => 'johndoe@nowhere.com',
            Custom__bAB__bField__bOne => 'Value of Custom AB Field One'
        },
        projfields =>
        {
            Custom__bField__bOne => 'Value of Custom Field One',
            Custom__bField__bTwo => 'Value of Custom Field Two'
        }
    }
);
my $result;
if( $soapenv->fault )
{
    print ${$soapenv->fault}{faultstring} . "\n";
    exit;
}
else
{
    $result = $soapenv->result;
}
print "Issue $result has been created.\n";
*/

