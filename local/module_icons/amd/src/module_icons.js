/**
 * Our javascript for module icons
 * @link https://docs.moodle.org/dev/Javascript_Modules
 * @link https://moodle.org/mod/forum/discuss.php?d=362880
 */
import $ from 'jquery';

export const init = () => {
  const selector = $('#id_icon_selector');
  if (selector) {
    const parent = selector.parent();
    const holder = $('<span />').attr('id', 'icon-selector-example').css({'display': 'inline-block', 'margin-left': '5px', 'margin-top': '3px'});
    parent.append(holder);
    const data = JSON.parse(selector.attr('data-urls'));
    let val = selector.val();
    let text = selector.find('option:selected').eq(0).text();
    if (val in data) {
      holder.html('<img src="'+data[val]+'" alt="'+text+'" />');
    }
    selector.on('change', (event) =>  {
      const elem = $(event.currentTarget);
      val = elem.val();
      if (val in data) {
        text = elem.find('option:selected').eq(0).text();
        holder.html('<img src="'+data[val]+'" alt="'+text+'" />');
      } else {
        holder.html('');
      }
    });
  }
};
