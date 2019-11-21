/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

const widget = {
  'url': '//widget.mondialrelay.com/parcelshop-picker/jquery.plugin.mondialrelay.parcelshoppicker.min.js',
  'loaded': false,
  'initialized': false,
  'widget_container': '#mondialrelay_widget',
  'save_container': '#mondialrelay_save-container',
  'error_container': '#mondialrelay_errors',
  'summary_container': '#mondialrelay_summary',
  'save_button': '#mondialrelay_save-relay',
  'change_button': '#mondialrelay_change-relay',
  'selected_relay_input': '#mondialrelay_selected-relay',
  'widget_search_zipcode_input': '#mondialrelay_widget .MRW-Search .Arg2',
  'widget_search_country_input': '#mondialrelay_widget .MRW-Search .Arg1',
  'savedRelay': null,
  // This is basically read-only; it's used to check the widget's current state
  'widget_current_params': null,
};

const loadJs = (url, callback) => {
  var script = document.createElement('script');
  script.onload = callback;
  script.src = url;
  
  document.head.appendChild(script);
};

widget.load = (callback) => {
  loadJs(widget.url, function() {
    widget.loaded = true;
    callback();
  });
};

widget.init = (params) => {
  
  // Access to the page may be completely blocked if the configuration isn't
  // done, so this variable may not always be defined.
  if (
    typeof MONDIALRELAY_BAD_CONFIGURATION != 'undefined'
    && MONDIALRELAY_BAD_CONFIGURATION
  ) {
    return;
  }
  
  if (widget.savedRelay) {
    $(widget.summary_container).slideDown();
  }
  
  if (!widget.loaded) {
    widget.addLoader(widget.widget_container);
    widget.load(function() {
      // No need to remove the loader; it will be replaced by the
      // widget's content
      widget.init(params);
    });
    return;
  }
  
  let defaultParams = {
    // Where to store the selected relay's ID
    Target: widget.selected_relay_input,
    Brand: MONDIALRELAY_ENSEIGNE,
    // iso code, 2 letters
    Country: MONDIALRELAY_COUNTRY_ISO,
    // Postcode for default search
    PostCode: MONDIALRELAY_POSTCODE,
    // Delivery mode; carrier dependent (Standard [24R], XL [24L], XXL [24X], Drive [DRI])
    // Here for information, but don't set a default value; we should always set
    // one directly
    // ColLivMod: '24R',
    // Wether to display the relays on a map or as a list
    ShowResultsOnMap: MONDIALRELAY_DISPLAY_MAP == true,
    // Nombre de Point Relais à afficher
    NbResults: "7",
    Responsive: true,
    OnParcelShopSelected: function(relayData) {
      $(widget.save_container).show();
      widget.setSelectedRelay(null, relayData);
    }
  };
  
  if (params) {
    $.extend(defaultParams, params);
  }

  widget.widget_current_params = defaultParams;
  $(widget.widget_container).MR_ParcelShopPicker(defaultParams);
  widget.initialized = true;
};

widget.show = () => {
  $(widget.widget_container).show();
};

widget.hide = () => {
  $(widget.widget_container).hide();
};

widget.update = (params) => {
  $(widget.widget_container).trigger("MR_SetParams", params);
  widget.widget_current_params = $.extend(widget.widget_current_params, params);
  widget.doSearch();
};

widget.initOrUpdate = (params) => {
  if (widget.initialized) {
    widget.update(params);
  } else {
    widget.init(params);
  }
};

widget.setSelectedRelay = (relayIdentifier, relayData) => {
  if (typeof relayIdentifier == 'undefined' || !relayIdentifier) {
    relayIdentifier = $(widget.selected_relay_input).val();
  }
  
  if (relayIdentifier != $(widget.selected_relay_input).val()) {
    // @TODO : select relay on map... if we can
    return;
  }
  
  // This can happen sometimes; we don't want to trigger a change if the value
  // didn't actually change
  if (MONDIALRELAY_SELECTED_RELAY_IDENTIFIER != relayIdentifier) {
    let oldRelay = MONDIALRELAY_SELECTED_RELAY_IDENTIFIER;
    MONDIALRELAY_SELECTED_RELAY_IDENTIFIER = relayIdentifier;
    $(widget).trigger('mondialrelay.selectedRelay', {oldRelay: oldRelay, relayData: relayData});
  }
};

widget.resetSelectedRelay = () => {
    let oldRelay = MONDIALRELAY_SELECTED_RELAY_IDENTIFIER;
    MONDIALRELAY_SELECTED_RELAY_IDENTIFIER = null;
    $(widget).trigger('mondialrelay.selectedRelay', {oldRelay: oldRelay, relayData: null});
};

widget.resetSavedRelay = (hideWidget) => {
    widget.savedRelay = null;
    
    if (typeof hideWidget === 'undefined' || !hideWidget) {
      $(widget.summary_container).slideUp();
      $(widget.widget_container).slideDown();
      $(widget.save_container).slideDown();
    }
};

widget.doSearch = (zipcode, country_iso) => {
  let searchCriteria = [
    typeof zipcode !== 'undefined' && zipcode ?  zipcode : $(widget.widget_search_zipcode_input).val(),
    typeof country_iso !== 'undefined' && country_iso ?  country_iso : $(widget.widget_search_country_input).val(),
  ];
  $(widget.widget_container).trigger('MR_DoSearch', searchCriteria);
};

widget.displayErrors = (messages) => {
  let box = $(widget.error_container);
  if (box.length == 0) {
    return;
  }
  
  box.stop(true);
  
  if (box.children().length) {
    box.fadeOut(function() {
      $(this).empty();
    });
  }
  
  if (typeof messages == 'undefined' || !messages) {
    return;
  }
  
  // Either the queue is already going with .fadeOut(), either it will be
  // launched by .fadeIn()
  box.queue(function() {
    
    let errors = [];
    $.each(messages, function(k, m) {
      errors.push($("<div/>").addClass("alert alert-danger").text(m));
    });
    
    $(this).append(errors);
    $(this).dequeue();
  });
  box.fadeIn();
};

widget.addLoader = (container) => {
  if ($(container).length == 0) {
    return;
  }
  
  if ($(container).children('.mondialrelay_loader').length > 0) {
    return;
  }
  
  $("#mondialrelay_loader-template")
    .clone()
    .attr('id', null)
    .show()
    .appendTo(container);
};

widget.removeLoader = (container) => {
  $(container).children('.mondialrelay_loader').remove();
};

$(document).ready(function() {
  widget.savedRelay = MONDIALRELAY_SELECTED_RELAY_IDENTIFIER;
  $(widget).trigger('mondialrelay.ready');
});

$(document).on('click', widget.change_button, function(ev) {
  widget.show();
});

$(document).on('click', '.MRW-ShowList', function(ev) {
    ev.preventDefault();
    ev.stopPropagation();
});

// Selecting a city from the autocomplete list may trigger things
$(document).on('click', '.PR-City', function(ev) {
  ev.preventDefault();
  ev.stopPropagation();
});

export { widget };