var waitInstall = 0;

function callSaved(text, type) {
  var $p = $('#ui_popup');
  $p.find('.fa')
    .attr('class', 'fa ' + (type === 1 ? 'fa-check-circle' : type === 2 ? 'fa-exclamation-triangle' : 'fa-times-circle'))
    .css('color', type === 1 ? '#28a745' : type === 2 ? '#ffc107' : '#dc3545');
  $p.find('.msg').text(text);
  $p.addClass('show');
  setTimeout(function() { $p.removeClass('show'); }, 3000);
}

function selectIt() {
  $("select:visible").selectBoxIt({
    autoWidth: false,
    hideEffect: 'fadeOut',
    hideEffectSpeed: 100
  });
}

function validateSection(id) {
  var ok = true;
  $('#' + id + ' .required').each(function() {
    if (!$(this).val().trim()) {
      ok = false;
      $(this).css('border-color', 'red');
    } else {
      $(this).css('border-color', '#ddd');
    }
  });
  return ok;
}

function showStep(stepId) {
  $('.step-btn, .installer-step').removeClass('active');
  $('.step-btn[data-target="' + stepId + '"]').addClass('active');
  $('#' + stepId).addClass('active');
}

function getComponent() {
  $.post('builder/encoded/element.php', { check: 1 }, function(html, status, xhr) {
    $('#install_content').html(xhr.status === 200 ? html : '<h2 style="color:#d9534f;text-align:center;">403 Forbidden</h2>');
  });
}

function getEnding() {
  $.post('builder/encoded/ending.php', { check: 1 }, function(html) {
    $('#install_content').html(html);
  });
}

function runInstaller() {
  if (waitInstall) return;
  waitInstall = 1;
  $('#install_component').hide();
  $('#wait_install').show();

  var data = {
    db_host: $('#install_db_host').val(),
    db_name: $('#install_db_name').val(),
    db_user: $('#install_db_user').val(),
    db_pass: $('#install_db_password').val(),
    title: $('#install_title').val(),
    domain: $('#install_domain').val(),
    username: $('#install_username').val(),
    email: $('#install_email').val(),
    password: $('#install_password').val(),
    repeat: $('#install_repeat').val(),
    language: $('#install_language').val(),
  };

  $.post('builder/encoded/component.php', data, function(resp) {
    if (resp.code == 1) {
      getEnding();
    } else {
      callSaved(resp.error, 3);
      waitInstall = 0;
      $('#wait_install').hide();
      $('#install_component').show();
    }
  }, 'json')
  .fail(function() {
    callSaved('Server error', 3);
    waitInstall = 0;
    $('#wait_install').hide();
    $('#install_component').show();
  });
}

function startInstall() {
  if ($('.accept_install').attr('value') !== '1') {
    callSaved('Please agree to the license first', 3);
    return;
  }
  $.post('builder/encoded/permission.php', { check: 1 }, function(resp) {
    if (resp === '1') {
      getComponent();
    } else {
      $('#install_content').html(resp);
      callSaved('Fix the errors above before continuing', 3);
    }
  });
}

function endInstall() {
  window.location.reload();
}

$(document).ready(function() {
  $('#start_install').on('click', startInstall);

  $(document).on('click', '.step-btn:not(.disabled)', function() {
    showStep($(this).data('target'));
  });

  $(document).on('click', '#next_user', function() {
    var email = $('#install_email').val().trim();
    var pwd   = $('#install_password').val();
    var pwd2  = $('#install_repeat').val();
    var re    = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!re.test(email)) {
      $('#install_email').css('border-color', '#dc3545');
      callSaved('Please enter a valid email address', 3);
      return;
    }
    $('#install_email').css('border-color', '#ddd');

    if (pwd !== pwd2) {
      $('#install_password, #install_repeat').css('border-color', '#dc3545');
      callSaved('Passwords do not match', 3);
      return;
    }
    $('#install_password, #install_repeat').css('border-color', '#ddd');

    if (validateSection('step_user')) {
      $('.step-btn[data-target="step_site"]').removeClass('disabled');
      showStep('step_site');
    } else {
      callSaved('Please fill all fields', 3);
    }
  });

  $(document).on('click', '#next_site', function() {
    if (validateSection('step_site')) {
      $('.step-btn[data-target="step_db"]').removeClass('disabled');
      showStep('step_db');
    } else {
      callSaved('Please fill all fields', 3);
    }
  });

  $(document).on('click', '#next_db', function() {
    if (validateSection('step_db')) {
      $('.step-btn[data-target="step_license"]').removeClass('disabled');
      showStep('step_license');
    } else {
      callSaved('Please fill all fields', 3);
    }
  });

  $(document).on('click', '.accept_install', function() {
    var $i = $(this),
        on = $i.attr('value') === '1';
    $i.attr('value', on ? '0' : '1')
      .toggleClass('fa-circle fa-check-circle');
    $('#start_install').prop('disabled', on);
  });
});