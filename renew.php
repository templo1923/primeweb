<?php

include "header.php";
include "config.php";
echo "\n<div class=\"topmoviestext2\">\n\t\t<div class=\"slideshow-container\">\n\t\t\t<div class=\"mySlides custfade\" style=\"display: block;\">\n\t\t\t\t<div class=\"notification-text-sec\">\n\t\t\t\t    <h4 class=\"notification-heading\">Account Expired</h4>\n\t\t\t\t    <input type=\"hidden\" value=\"Announcement\" id=\"notificationType\">\n\t\t\t\t    <input type=\"hidden\" value=\"\" id=\"notificationReferenceId\">\n\t\t            <div class=\"notification-subheading-sec\"><p>Please Renew Your Account<br><br></p></div>\n\t\t            <div class=\"notification-subheading-sec\"><p><a href=\"";
echo $get_dir . "/logout";
echo "\">Go Back To Login</a><br><br></p></div>\n                </div>\n\t\t\t\t<div class=\"notification-image\">\n\t\t\t\t\t<img src=\"http://ltq.lttelevision.com/assets/uploads/files/c8fbc-red-attention-sign-png-no-background.png\" style=\"\">\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n</div>";

?>