<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd" >
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <META NAME="robots" CONTENT="noindex,nofollow">
    <title>
    MacLean Power Systems Authorized Credit Card Payment Confirm
    </title>
<style type="text/css">
  label {
      display: block;
      margin: 5px 0px;
      color: black;
      font-size: 25px;
   }
   input {
      display: block;
      font-size: 25px;
   }
   input[type=submit] {
      margin-top: 20px;
      background-color:#666666;
      color:#fff;
   }
   footer{
    position: absolute;
    bottom: 0;
    background-color:#000;
    color:#fff;
    width: 100%;
    height: 2.5rem; 

   }

</style>
<nav class="navbar navbar-expand-sm navbar-toggleable-sm navbar-light bg-dark border-bottom box-shadow mb-3">
                <div class="container" style="background-color:black;" ></br>
                    <img src="<?php echo plugin_dir_url(__FILE__) . '/MacLean_Logo.png' ?>" width="300" height="55"></br>
                    </br>
                    </br>
                </div>
</nav>
  </head>
  <body>
  <h1 style="text-align:center;">
    Verify and click Continue for payment information.</br>

  </h1>
  <center>
    <form action="https://checkout.globalgatewaye4.firstdata.com/payment" method="POST" >

<?php
include 'calcs.php';
$x_login = loginName();
$transaction_key = tranKey();
$x_amount = $_POST["x_amount"];
$x_currency_code = "USD"; // Needs to agree with the currency of the payment page
srand(time()); // initialize random generator for x_fp_sequence
$x_fp_sequence = seqNum();
$x_fp_timestamp = getTMStamp();
$x_user1 = "";
$x_user2 = "";
$x_user3 = "";
$x_fp_hash = genHash($x_amount, $x_currency_code);
echo ('<input type="Hidden"  name="x_login" value="' . $x_login . '">');
echo ('<input type="Hidden"  name="x_fp_sequence" value="' . $x_fp_sequence . '">');
echo ('<input type="Hidden"  name="x_fp_timestamp" value="' . $x_fp_timestamp . '">');
echo ('<input type="Hidden"  name="x_fp_hash" value="' . $x_fp_hash . '" size="50">');
echo ('<input type="Hidden" name="x_currency_code" value="' . $x_currency_code . '">');
echo ('<label>Transaction ID:' . $_POST["x_user1"] . '</label><input  type="Hidden" name="x_user1" value= ' . $_POST["x_user1"] . ' readonly>');
echo ('<label>Customer Name:' . $_POST["x_user2"] . '</label><input type="Hidden" name="x_user2" value= ' . $_POST["x_user2"] . ' readonly>');
echo ('<label>Customer ID: ' . $_POST["x_user3"] . '</label><input type="Hidden" name="x_user3" value= ' . $_POST["x_user3"] . ' readonly>');
echo ('<label>Amount To Pay:' .$x_amount. '</label><input type="Hidden" name="x_amount" value=' . $x_amount . ' readonly>');
?>

     <input type="hidden" name="x_show_form" value="PAYMENT_FORM"/>
      <input type="submit" value="Continue"/>
    </form>
    <p>Questions- Please contact MacLean Power Accounts Receivable , mpscreditcards@macleanpower.com</p>
  </center>
  <footer>
<p>© <?php echo date("Y");?> MacLean Power Systems. All rights reserved.</p>
</footer>
</body>
</html>