(function($)
{
    var cometd = $.cometd;

    $(document).ready(function()
    {
        function _connectionEstablished()
        {
            cometd.batch(function() {
                //subscribe to ticket events
                cometd.subscribe("/ticket/"+config.ticket_id, function(message) {
                    _update_ticket(message.data);
                });

                //receive presence list (as response to /service/ticket/members)
                cometd.subscribe('/ticket/members/'+config.ticket_id, function(message) {
                    _update_presence(message.data);
                });
                cometd.publish('/service/ticket/members', { ticket_id: config.ticket_id, contact_name: config.contact_name });
            });
        }

        function _connectionClosed()
        {
            //$('#body').append('<div>CometD Connection Closed</div>');
        }

        // Function that manages the connection status with the Bayeux server
        var _connected = false;
        function _metaConnect(message)
        {
            if (cometd.isDisconnected())
            {
                _connected = false;
                _connectionClosed();
                return;
            }

            var wasConnected = _connected;
            _connected = message.successful === true;
            if (!wasConnected && _connected)
            {
                _connectionEstablished();
            }
            else if (wasConnected && !_connected)
            {
                //connection closed
            }
            if(!_connected) {
                _handshakeFailed();
            } 
        }

        // Function invoked when first contacting the server and
        // when the server has lost the state of this client
        function _metaHandshake(handshake)
        {
            var auth = handshake.ext && handshake.ext.authetication;
            if(auth && auth.failed === true) 
            {
                //authentication failed..    
                window.alert("Authentication failed");
            } else {
                
                if (handshake.successful === true)
                {
                    cometd.batch(function() {

                    });
                } else {
                    _handshakeFailed();
                }
            }
        }

        // Disconnect when the page unloads
        $(window).unload(function()
        {
            cometd.disconnect(true);
        });

        cometd.addListener('/meta/handshake', _metaHandshake);
        cometd.addListener('/meta/connect', _metaConnect);
    });
})(jQuery);
