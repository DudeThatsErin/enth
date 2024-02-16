# Enthusiast for PHP8 [Erin's Fork]
This is the main repo for Enthusiast 1.1 for PHP8.

UPDATE 2/15/24- NO LONGER SUPPORTED BY ME. If anyone wants to take it over that's fine, go ahead and fork it. I don't have the time or energy to support this. I thought I would but I do not.

## Credits
The original author is [Angela Sabas](http://scripts.indisguise.org/). Angela's ReadMe can be found [here](https://github.com/DudeThatsErin/enth/blob/main/Old%20Readmes/Angela%20README.md).

[Lysianthus](https://github.com/Lysianthus/enthusiast) also contributed.

The previous author is [Ekaterina](https://scripts.robotess.net/). Ekaterina's ReadMe can be found [here](https://github.com/DudeThatsErin/enth/blob/main/Old%20Readmes/Ekaterina%20README.md).

*Note: Ekaterina's version is not recommended for new installations. Mine **can** be used for new installations.*

# Installation Instructions

These are the instructions you will want to follow when you are installing LA for the first time.

## Step 1 - Download an archive.

To download an archive, you can do that 1 of 2 ways.  You will want to download these to your desktop or somewhere you can easily find them.
*Note: The pictures below are for listing admin but the steps are exactly the same.*

### First Way - Code > Download Zip
This will download a zip. You can see below what I am referring to. You can see the green "code" button above. Click that and click on "download zip".

<img width="389" alt="pLrKZvz3FV" src="https://github.com/DudeThatsErin/listingadmin/assets/2681022/24c1b732-9aff-4fce-89d5-cc942478c994">

### Second Way - Releases > Download "enth-x.x.x.zip"
The second way you can download an archive is by clicking on releases on the right sidebar...

<img width="244" alt="firefox_eZXuqbcl7f" src="https://github.com/DudeThatsErin/listingadmin/assets/2681022/f181aa9e-25d5-4415-b406-eee5445dc4af">

Then viewing the "Assets" and downloading the file that says `enth-x.x.x.zip`

<img width="898" alt="bLLgVrtods" src="https://github.com/DudeThatsErin/listingadmin/assets/2681022/feb8be7e-47b6-432c-a0d1-4896959b370e">


## Step 2 - Upload all files to your collective.

You will want to unzip the archive and upload everything inside the  `public` folder to your folder.

## Step 3 - Update Database Details for your Collective + Fanlistings
Find the `config.sample.php` file that you downloaded and open it up in your editor. Can be NotePad though I recommend NotePad++, Visual Studio Code, or Sublime. Either way, just open it so you can see the lines of code.

Find these lines:
```php
$db_server = 'localhost';
$db_user = 'username';
$db_password = 'password';
$db_database = 'database';
```
Update those details to your details that you found in Step 1. Then save and close the file.

In your file explorer, rename the file from `config.sample.php` to `config.php` (make sure there is only one `.php` at the end) and upload to your fanlisting collective.

**For your fanlistings, you will need to find the following line (at the end of the config file) and update it so that the listing ID matches the listing ID for your fanlistings**
```php
$listing = 1;
```
This line **must** be commented out for your fanlisting _collective_ but must be uncommented (no `//` before it) for your fanlistings.

Finally, in your `admin/` directory do the same thing that you just did with the `config.sample.php` file. You will be updating the database details (they look the same in this file as well) and renaming the file to `config.php`.

## Step 4 - Visit your collective to make sure it works!
This should 100% work on the first try. If it doesn't, make sure you read these steps carefully. If it doesn't and you have re-read these instructions, open an issue (at the top) and let me know what you have tried.


# Update Instructions
*Note: I am **not** providing support for versions lower than 1.0.6.*

If you are using Enthusiast 1.0.6 (beta) (the old version by Tess) please follow her readme, though I **highly** recommend against that.

## Step 1 - Backup, Backup, BACKUP!
Backup all of your current Listing Admin configurations, files, and databases first. ALWAYS do this before doing any install or upgrade.

Also, take note of your database information (I refer to as *variables*) in all of your `config.php` files.

## Step 2 - Download an archive.

To download an archive, you can do that 1 of 2 ways.  You will want to download these to your desktop or somewhere you can easily find them.
*Note: The pictures below are for listing admin but the steps are exactly the same.*

### First Way - Code > Download Zip
This will download a zip. You can see below what I am referring to. You can see the green "code" button above. Click that and click on "download zip".

<img width="389" alt="pLrKZvz3FV" src="https://github.com/DudeThatsErin/listingadmin/assets/2681022/24c1b732-9aff-4fce-89d5-cc942478c994">

### Second Way - Releases > Download "enth-x.x.x.zip"
The second way you can download an archive is by clicking on releases on the right sidebar...

<img width="244" alt="firefox_eZXuqbcl7f" src="https://github.com/DudeThatsErin/listingadmin/assets/2681022/f181aa9e-25d5-4415-b406-eee5445dc4af">

Then viewing the "Assets" and downloading the file that says `enth-x.x.x.zip`

<img width="898" alt="bLLgVrtods" src="https://github.com/DudeThatsErin/listingadmin/assets/2681022/feb8be7e-47b6-432c-a0d1-4896959b370e">

## Step 3 - Replace your current files with the new files
Replace the files inside your `admin/` directory (folder) with the `admin/` files from this repository. Make sure that you have all files from the folder uploaded.

## Step 4 - Update Database Details for your Collective + Fanlistings
Find the `config.sample.php` file that you downloaded and open it up in your editor. Can be NotePad though I recommend NotePad++, Visual Studio Code, or Sublime. Either way, just open it so you can see the lines of code.

Find these lines:
```php
$db_server = 'localhost';
$db_user = 'username';
$db_password = 'password';
$db_database = 'database';
```
Update those details to your details that you found in Step 1. Then save and close the file.

In your file explorer, rename the file from `config.sample.php` to `config.php` (make sure there is only one `.php` at the end) and upload to your fanlisting collective.

**For your fanlistings, you will need to find the following line (at the end of the config file) and update it so that the listing ID matches the listing ID for your fanlistings**
```php
$listing = 1;
```
This line **must** be commented out for your fanlisting _collective_ but must be uncommented (no `//` before it) for your fanlistings.

Finally, in your `admin/` directory do the same thing that you just did with the `config.sample.php` file. You will be updating the database details (they look the same in this file as well) and renaming the file to `config.php`.

## Step 4 - Visit your collective to make sure it works!
This should 100% work on the first try. If it doesn't, make sure you read these steps carefully. If it doesn't and you have re-read these instructions, open an issue (at the top) and let me know what you have tried.

## Step 5 - Visit your collective to make sure it works!
This should 100% work on the first try. If it doesn't, make sure you read these steps carefully. If it doesn't and you have re-read these instructions, open an issue (at the top) and let me know what you have tried.

# Questions?
## What are the `samplecollective` and `samplefl` for?
They are folders that were added by either Angela or Ekaterina for previewing how your fanlisting and collective might look. They are there for convenience. You can delete these or keep them to reuse for future fanlistings.

## Future questions will be added later!
As they come up I will add more FAQ here. :)
