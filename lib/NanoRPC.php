<?php

	/*

	Read full RPC documentation at https://github.com/nanocurrency/nano-node/wiki/RPC-protocol or https://developers.nano.org/docs/rpc
	
	USAGE:

	Include NanoRPC.php
		
		require_once 'PATH/php4nano/lib/NanoRPC.php';

	Initialize Nano connection/object
	
		$nanorpc = new NanoRPC();

	Optionally, you can specify host and port.
	
		$nanorpc = new NanoRPC( 'localhost', '7076' );

	If you wish to setup a SSL connection you can set an optional CA certificate or leave blank
	This will set the protocol to HTTPS and some CURL flags
	
		$nanorpc->setSSL('/full/path/to/mycertificate.cert');

	Example of call:

		$args =
		[
			'account' => 'nano_1abcp8j755owefwsxcbww56jqmimsojy1xxduz7m3idze677hkrnjs98da55'
		];
		
		$response = $nanorpc->account_balance( $args );
		
		echo $response['balance'];

	The full response (not usually needed) is stored in $this->response while the raw JSON is stored in $this->response_raw

	When node fails for any reason, it will return FALSE and put the error message in $this->node_error
	
		echo $nanorpc->node_error;
	
	When a call fails for any reason, it will return FALSE and put the error message in $this->error
	
		echo $nanorpc->error;

	The HTTP status code can be found in $this->status and will either be a valid HTTP status code or will be 0 if cURL was unable to connect
	
		echo $nanorpc->status;

	*/
	
	
	
	class NanoRPC
	{
		
		// Configuration options
		
		private $proto;
		
		private $host;
		
		private $port;
		
		private $url;
		
		private $CACertificate;

		// Information and debugging
		
		public $status;
		
		public $error;
		
		public $response_raw;
		
		public $response;
		
		private $id = 0;
		
		
		 
		function __construct( string $host = 'localhost', string $port = '7076', string $url = null )
		{
			
			$this->host = $host;
			
			$this->port = $port;
			
			$this->url = $url;
			
			$this->proto = 'http';
			
			$this->CACertificate = null;
			
		}
		 
		function setSSL( string $certificate = null )
		{
			
			$this->proto = 'https';
			
			$this->CACertificate = $certificate;
			
		}

		function __call( $method, array $params )
		{
			
			$this->status = null;
			
			$this->error = null;
			
			$this->node_error = null;
			
			$this->response_raw = null;
			
			$this->response = null;

			$this->id++;
			
			$request =
			[
				'action' => $method
			];
			
			if( isset( $params[0] ) )
			{
				
				foreach( $params[0] as $key => $value )
				{
					$request[$key] = $value;
				}
				
			}
			
			$request = json_encode( $request );

			// Build the cURL session
			
			$curl = curl_init( "{$this->proto}://{$this->host}:{$this->port}/{$this->url}" );
			
			$options =
			[
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_USERAGENT => 'PHP',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_HTTPHEADER => ['Content-type: application/json'],
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $request
			];

			// This prevents users from getting the following warning when open_basedir is set:
			
			// Warning: curl_setopt() [function.curl-setopt]: CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
			
			if( ini_get('open_basedir') )
			{		
				unset( $options[CURLOPT_FOLLOWLOCATION] );	
			}

			if( $this->proto == 'https' )
			{
				
				// If the CA Certificate was specified we change CURL to look for it
				
				if( $this->CACertificate != null )
				{
					
					$options[CURLOPT_CAINFO] = $this->CACertificate;
					
					$options[CURLOPT_CAPATH] = DIRNAME( $this->CACertificate );
					
				}
				else
				{
					
					// If not we need to assume the SSL cannot be verified so we set this flag to FALSE to allow the connection
					
					$options[CURLOPT_SSL_VERIFYPEER] = false;
					
				}
				
			}

			curl_setopt_array( $curl, $options );

			// Execute the request and decode to an array
			
			$this->response_raw = curl_exec( $curl );
			
			$this->response = json_decode( $this->response_raw, true );

			// If the status is not 200, something is wrong
			
			$this->status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
			
			// If there was no error, this will be an empty string
			
			$curl_error = curl_error( $curl );

			curl_close( $curl );
			
			if( isset( $this->response['error'] ) )
			{
				$this->node_error = $this->response['error'];
			}

			if( !empty( $curl_error ) )
			{
				$this->error = $curl_error;
			}

			if( $this->status != 200 )
			{
				
				// If node didn't return a nice error message, we need to make our own
				
				switch( $this->status )
				{
					
					case 400:
					
						$this->error = 'HTTP_BAD_REQUEST';
						
						break;
						
					case 401:
					
						$this->error = 'HTTP_UNAUTHORIZED';
						
						break;
						
					case 403:
					
						$this->error = 'HTTP_FORBIDDEN';
						
						break;
						
					case 404:
					
						$this->error = 'HTTP_NOT_FOUND';
						
						break;	
						
				}	
				
			}

			if( $this->error )
			{
				return false;
			}
				
			return $this->response;
			
		}
		
	}

?>