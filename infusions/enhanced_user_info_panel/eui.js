$(document).ready(function()
{
  $(".eui_body").hide();
  $(".eui_master_head").click(function()
  {
    $(this).next(".eui_master_body").slideToggle(600);
  });
  $(".eui_head").click(function()
  {
    $(this).next(".eui_body").slideToggle(600);
  });
});