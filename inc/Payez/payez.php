<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd" >
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <META NAME="robots" CONTENT="noindex,nofollow">
    <title>
    MacLean Power Systems Authorized Credit Card Payment
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

    </img>

  <h1 style="text-align:center;">
  MacLean Power Systems Authorized Credit Card Payment
  </h1>

  <center>
  <form action="/payez-confirm" method="POST" >

<?php
include '/calcs.php';

$x_amount = "0";
$x_user2 = "";
$x_user3 = "";

echo ('<label>Transaction ID</label><input name="x_user1" value="' . $x_user1 . '">');
echo ('<label>Customer Name</label><input name="x_user2" value="' . $x_user2 . '">');
echo ('<label>Customer ID</label><input name="x_user3" value="' . $x_user3 . '">');
echo ('<label>Amount To Pay</label><input name="x_amount" value="' . $x_amount . '">');

?>

      <input type="hidden" name="x_show_form" value="PAYMENT_FORM"/>
      <input type="submit" value="Next"/>
    </form>
<p>Questions- Please contact MacLean Power Accounts Receivable , mpscreditcards@macleanpower.com</p>

  </center>

<footer>
<p>© <?php echo date("Y");?> MacLean Power Systems. All rights reserved.</p>
</footer>
  </body>
</html>