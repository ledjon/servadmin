//
// $Id: ajax_class.js,v 1.9 2006/01/24 19:09:33 ledjon Exp $
//
// AJAX class (javascript side)
// by Jon Coulter
//

// global ajax http handle
var aj_http = null;
var aj_self = null;
var aj_wait = false;

// queue item
var aj_queue = new AJAX_Queue( );

//var aj_handles = array( );

// ** class elements defined here ** //
function AJAX_Request( )
{
	this.http = null;
	this.debug = 0;
	this.handler = null;
	this.async = true;
	
	this.loadHTTP = ajax_loadHTTP;
	this.sendRequest = ajax_sendRequest;
	this.formToPostData = ajax_formToPostData;
	this.sendRequestData = ajax_sendRequestData;
	
	this._reqHandler = ajax_reqHandler;
	
	this.checkWait = ajax_checkWait;
}

function ajax_checkWait( )
{
	// nothing here yet
}

function ajax_loadHTTP( )
{
	this.http = null;

	// code for Mozilla, etc.
	if (window.XMLHttpRequest)
	{
		this.http = new XMLHttpRequest()
	}
	// code for IE
	else if (window.ActiveXObject)
	{
		this.http = new ActiveXObject("Microsoft.XMLHTTP")
	}
	else
	{
		alert('Your web browser does not support XMLHTTP!');
		return false;
	}

	return true;
}

function ajax_sendRequest( url, rHandler )
{
	this.checkWait( );
	
	if(! this.loadHTTP( ) )
	{
		return;
	}
	
	this.handler = rHandler;

	this.http.onreadystatechange = function( ) { ref._reqHandler( ); };
	this.http.open("GET", url, this.async);

	this.http.send( null );
}

function ajax_sendRequestData( url, data, rHandler )
{
	this.checkWait( );
	
	if(! this.loadHTTP( ) )
	{
		return;
	}
	
	this.handler = rHandler;

	// need to create reference for 'this'
	var ref = this;

	this.http.onreadystatechange = function( ) { ref._reqHandler( ); };
	
	this.http.open("POST", url, this.async);

	this.http.setRequestHeader('Content-Type',
		'application/x-www-form-urlencoded');

	this.http.send(data);
}

function ajax_formToPostData( form )
{
	var d = AJAX_Data( );
	d.from_form( form );
	
	return d.as_string( );
}

// default request handler
function ajax_reqHandler( )
{
	if(this.http.readyState == 4)
	{
		if(this.http.status == 200)
		{
			if(this.debug)
			{
				//alert(this.http.responseXML.documentElement);
				//alert('this is the callback handler');
				//alert(this._reqHandler);
				alert(this.http.responseText);
			}
			
			// pass to the real handler
			this.handler( this.http.responseText );
		}
		else
		{
			alert('Error retrieving response for request:' + this.http.responseText);
		}
	}
}

// AJAX Element Class
// Helps to build up a key/value set of items
// by Jon Coulter

// ** Data (key -> value) class ** //
function AJAX_Data( )
{
	this._parts = new Array( );
	
	this.as_string = ajd_AsString;
	
	this.from_form = ajd_FromForm;
	this.to_form = ajd_ToForm;
	
	this.add = ajd_Add;
	this.exists = ajd_Exists;
	this.remove = ajd_Remove;
	this.size = ajd_Size;
	this.keys = ajd_Keys;
	this.get = ajd_Get;
}

function ajd_AsString( )
{
	var ret = '';
	
	for(var k in this._parts)
	{
		var val = this._parts[k];
		
		if(val != null)
		{
			if(ret != '' && ret != null)
			{
				ret += '&';
			}
			
			ret += k + '=' + escape(val);
		}
	}
	
	return ret;
}

function ajd_FromForm( form )
{
	for (var i = 0; i < form.elements.length; i++)
	{
		var elm = form.elements[i];

		if(this.exists( elm.name ))
		{
			this.remove(elm.name);
		}
		
		if(elm.tagName == 'INPUT')
		{
			if(elm.type == 'checkbox'
				|| elm.type == 'radio'
			)
			{
				if(elm.checked == true)
				{
					this.add(elm.name, elm.value);
				}
			}
			else
			{
				this.add(elm.name, elm.value);
			}
		}
		else
		{
			this.add(elm.name, elm.value);
		}
	}
}

//
// convert the current objects
// data into a form (if the element exists)
// 
function ajd_ToForm( form )
{
	keys = this.keys( );
	
	for(var i in keys)
	{
		var key = keys[i];
		//var elm = document.getElementById(key);
		var elm = eval('form.' + key);
		
		if(elm != null)
		{
			elm.value = this.get(key);
		}
	}
}

function ajd_Add( k, v )
{
	this._parts[k] = v;
}

function ajd_Exists( k )
{
	var exists = false;
	
	for(var i in this._parts)
	{
		if(i == k && this._parts[i] != null)
		{
			exists = true;
			break;
		}
	}
	
	return exists;
}

function ajd_Remove( k )
{
	var v = this._parts[k];
	this._parts[k] = null;
	return v;
}

function ajd_Size( )
{
	var c = 0;
	for(var i in this._parts)
	{
		c++;	
	}
	
	return c;
}

function ajd_Keys( )
{
	var s = this.size( );
	var ret = new Array( s );
	var x = 0;
	
	for(var i in this._parts)
	{
		ret[x++] = i;
	}
	
	return ret;
}

function ajd_Get( k )
{
	return this._parts[k];
}

// AJAX XML Class
// Create a simple xml document to form data (or parse it)
// 
// by Jon Coulter

// ** Class function ** //
function AJAX_Serializer( )
{
	this._data = null;
	
	//this.setData = ajs_setData;
	this.Deserialize = ajs_Deserialize;
	this.Serialize = ajs_Serialize;
}

function ajs_setData( data )
{
	this._data = data;
}

// input should be the
// data from the calling script
// and output is an AJAX_Data object
function ajs_Deserialize( data )
{
	if(!data)
	{
		//throw new Exception("Unable to deserialize data");
	}

	var d = new AJAX_Data( );
	d.raw = data;
	
	data = data.replace('\r', '');
	var parts = data.split('\n');
	//alert(parts);
	
	for(var i = 0; parts[i] != null; i++)
	{
		kv = parts[i].split('=', 2);
		
		//alert(kv[0] + kv[1]);
		var k = kv[0];
		var v = unescape(kv[1]);
		d.add(k, v);
	}
	
	return d;
}

function ajs_Serialize( obj )
{
	// 'obj' needs to be an AJAX_Data object
	var ret = '';
	
	keys = obj.keys( );
	
	for(var i in keys)
	{
		if(ret != '')
		{
			ret += '\n';
		}

		//ret += keys[i] + '=' + escape(obj.get(keys[i]));
		// Jon Coulter, 1/24/2006
		// escape doesn't take care of + signs, I guess!
		ret += keys[i] + '=' + escape(obj.get(keys[i])).replace('+', '%2B');
	}

	//alert(ret);
	
	return ret;
}

// AJAX Transport
// Handles the communication
// between the client (js) and server (php/perl/asp...)
// for my specific protocol (which replaces xml)
// 
// Acts as a glue to all the other classes
//
// by Jon Coulter

// ** Class def ** //
function AJAX_Handler( )
{
	//this.setData = ajt_setData;
	this.ProcessResponse = ajt_ProcessResponse;
	this.sendRequest = ajt_sendRequest;
}

function ajt_ProcessResponse( data )
{
	this.raw = data;
	// the incoming data is 
	// a double seralized AJAX_Data object
	var s = new AJAX_Serializer( );
	
	var d1 = s.Deserialize( data );
	var indata = d1.get('_ajax_response');

	if(! indata )
	{
		return null;
	}
	
	var d = s.Deserialize( indata );
	
	return d;
}

function ajt_sendRequest( url, d, rHandler )
{
	var req = new AJAX_Request( );
	
	if(d != null)
	{
		// d = AJAX_Data object
		var s = new AJAX_Serializer( );

		// this is the incoming data
		var sdata = s.Serialize( d );

		// now we need to form a quasi-post object
		var post = new AJAX_Data( );

		post.add('_ajax_data', sdata);
		post.add('_ajax_session', '');

		// form it into a post-worthy string the data for the posting
		var data = post.as_string( );

		//alert(data);

		req.sendRequestData(url, data, rHandler);
	}
	else
	{
		req.sendRequest(url, rHandler);
	}
}

/*
	A class to handle the queue'ing of ajax requests.
	
	This is an attempt to get around the lack of concurrent requests
	that can easily be obtained using ajax
	
	by Jon Coulter
	
	Logic:
	 - Create a queue object (on page load)
	 - Add items to the queue as needed durring the events of the
	   application running.
	 - On queue-add, process queue items (if the added item is #1, or only item)
	   -- this kicks off the process
	 - On queue-complete, process queue items (if any)
	   -- requires a 'lock' variable?
*/

function AJAX_Queue( )
{
	// queue array
	// ...
	this._queue = new Array();
	
	// current request items
	this._currentRequest = null;
	this._inRequest = false;
	
	// public methods
	this.Add = ajq_add;
	this.add = ajq_add;
	
	// private methods
	this.InRequest = ajq_InRequest;
	this.ExecNext = ajq_ExecNext;
	this.QueueSize = function() { return this._queue.length; };
	this.AddQueue = ajq_addQueue;
}

function ajq_InRequest( )
{
	return this._inRequest
}

// aj_queue.Add( url, data, myHandler[, self_object] );
function ajq_add( url, data, rHandler, retObject )
{
	this.AddQueue( new AJAX_QueueItem( url, data, rHandler, retObject ) );
	
	// only item added so far?
	if(this.QueueSize() == 1)
	{
		this.ExecNext( );
	}
}

function ajq_addQueue( q )
{
	this._queue.push(q);
}

function ajq_ExecNext( )
{
	if(this.QueueSize() > 0)
		//&& this.InRequest() == false)
	{
		this._inRequest = true;
		
		// execute the enxt queue'd item
		var item = this._queue.pop();
		
		// Set the item to the current request
		// this is used on the callback method
		this._currentRequest = item;
		
		//alert('next item');
		
		var h = new AJAX_Handler( );

		// sendRequest(url, new AJAX_Data(), myHandler);
		h.sendRequest(item.Url, item.Data, ajq_rHandler);
	}
	
	return true;
}

function ajq_rHandler( data )
{
	var h = new AJAX_Handler( );
	var d = h.ProcessResponse( data );

	// no data to return
	if(! d )
	{
		d = new Object( );
		d.error = 1;
		d.raw = h.raw;
	}
	
	// current queue item
	var curItem = aj_queue._currentRequest;
	
	if(curItem == null)
	{
		alert('Unable to find the current request item!');
		return false;
	}
	
	if(curItem.Handler)
	{
		// call the handler
		curItem.Handler( d, curItem.retObject );
	}
	else
	{
		alert('No defined handler for this event.');
		return false;
	}
	
	// next queue item?
	// other items can proceed
	this._inRequest = false;
	aj_queue.ExecNext( );
}

/*
	AJAX Queue Item
	
	Used interally by the AJAX_Queue class
	
	by Jon Coulter
*/
function AJAX_QueueItem( url, data, handler, retObject )
{
	this.Url = url;
	this.Data = data;
	this.Handler = handler;
	this.retObject = retObject;
}

//
// Utility functions
//

// Called by the 'handler' method
// and will basically pass back the data
// that was provided (or a data objcet, rather)
function AJAX_GET_DATA( data )
{
	var h = new AJAX_Handler( );
	var d = h.ProcessResponse( data );
	
	return d;
}

// takes data as passed to a handler method
// and returns the data object
function AJAX_DESERIALIZE( data )
{
	var s = new AJAX_Serializer( );
	
	return s.Deserialize( data );
}
