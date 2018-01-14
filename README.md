MultiChain Web Demo
===================

MultiChain Web Demo is a simple web interface for [MultiChain](http://www.multichain.com/) blockchains, written in PHP.

https://github.com/MultiChain/multichain-web-demo

    Copyright(C) 2015,2016 by Coin Sciences Ltd.
    License: GNU Affero General Public License, see the file LICENSE.txt.


Welcome to MultiChain Web Demo
==============================

This software uses PHP to provide a web front-end for a [MultiChain](http://www.multichain.com/) blockchain node.

It currently supports the following features:

* Viewing the node's overall status.
* Creating addresses and giving them real names (names are visible to all nodes).
* Changing permissions for addresses.
* Issuing assets, including custom fields and uploading a file.
* Updating assets, including issuing more units and updating custom fields and file.
* Viewing issued assets, including the full history of fields and files.
* Sending assets from one address to another.
* Creating, decoding and accepting offers for exchanges of assets.
* Creating streams.
* Publishing items to streams, either as text or an uploaded file.
* Viewing stream items, including listing by key or publisher and downloading files.

The web demo does not yet support the following important functionality in the MultiChain API:

* Multisignature addresses and transactions.
* Adding metadata (or stream items) to permissions or asset transactions.
* Viewing an addresses' transactions.
* Subscribing to assets and viewing their transactions.
* Viewing a list of keys or publishers in a stream.
* Peer-to-peer node management.
* Message signing and verification.

The MultiChain Web Demo is still under development, so please [contact us](http://www.multichain.com/contact-us/) if any of these things are crucial for your needs.


System Requirements
-------------------

* A computer running web server software such as Apache.
* PHP 5.x or later with the `curl` extension.
* MultiChain 1.0 alpha 26 or later.

**Note that this Web Demo does not yet support MultiChain 2.0 preview releases.**


Create and launch a MultiChain Blockchain
-----------------------------------------

If you do not yet have a chain to work with, [Download MultiChain](http://www.multichain.com/download-install/) to install MultiChain and create a chain named `chain1` as follows:

    multichain-util create chain1
    multichaind chain1 -daemon
    
If your web server is running on the same computer as `multichaind`, you can skip the rest of this section. Otherwise:

    multichain-cli chain1 stop

Then add this to `~/.multichain/chain1/multichain.conf`:

    rpcallowip=[IP address of your web server]
  
Then start MultiChain again:
  
    multichaind chain1 -daemon



Configure the Web Demo
----------------------

_This section assumes your blockchain is named `chain1` and you are running the node and web server on a Unix variant such as Linux. If not, please substitute accordingly._

Make your life easy for the next step by running these on the node's server:

    cat ~/.multichain/chain1/multichain.conf
    grep rpc-port ~/.multichain/chain1/params.dat
    
In the web demo directory, copy the `config-example.ini` file to `config.ini`:

	cp config-example.ini config.ini

or on Windows:

	copy config-example.ini config.ini

  
In the demo website directory, enter chain details in `config.ini` e.g.:

    [default]
    name=Default                ; name to display in the web interface
    rpchost=127.0.0.1           ; IP address of MultiChain node
    rpcport=12345               ; usually default-rpc-port from params.dat
    rpcuser=multichainrpc       ; username for RPC from multichain.conf
    rpcpassword=mnBh8aHp4mun... ; password for RPC from multichain.conf

Multiple chains are supported by the web demo by copying the same section again but with different string for the section name in the square brackets, for example:

    [another]
	name=...
	rpchost=...
	...

**Note that the `config.ini` file is readable by users of your web demo installation, and contains your MultiChain API password, so you should never use this basic setup for a production system.**

For additional security the `config.ini` file can be placed in a parent directory of the web demo. By default the `read_config()` function will look for the specified configuration file up to three directory levels above the web demo.

Launch the Web Demo
-------------------

No additional configuration or setup is required. Based on where you installed the web demo, open the appropriate address in your web browser, and you are ready to go!

From PHP 5.4.0 it's possible to run the web demo from the command line using the built-in web server. To run the web demo using the provided web server, execute the following command from within the web demo directory:

    php -S localhost:8080 -t .

The web demo application will now be accessible through your web browser on the "`http://localhost:8080/`" address.
