# PostaleAPIDemo
Some scripts I use for accessing postale.io API for self service</br>

The index page has a link to all the tools, I have created these scripts at various points so the API entry needs to be completed for every PHP page, I might tidy this up at some point. </br></br>

You need to edit them to add your own postale.io key, these pages should not be exposed to the internet as they have your Postale API key. The reset password one is very problematic, so I would recommend not using it. I created it so my partner could reset her account at home... It is seriously easy to abuse!</br></br>

The pages are:<br>
<ul>
<li>alias.html / process_alias.php - Allow users to create forwarders and aliases</li>
<li>alias.php - Search form for Aliases</li>
<li>allaccounts.htm / get_mailboxes.php - Lists all mailboxes and allows a csv export</li>
<li>>allaliases.htm / get_aliases.php - Lists all aliases and allows a csv export</li>
<li>email_logs.htm / get_email_logs.php - Returns the email logs for a chosen date</li>
<li>index.html - An HTML page with a link to all the tools</li>
<li>list_domains.htm / get_domains.php - Lists all the domains in the account</li>
<li>password_reset.php - Allow users to reset their password, this is very, very, very easy to abuse, it doesn't allow password changes for domain or global admin accounts</li>
</ul>

![Index Page](https://github.com/user-attachments/assets/0046e99d-8093-4b65-be3a-2d1e6e9fd548)

