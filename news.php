<?php

include "functions.php";

printHeader();
?>
<div id="twitterfeed" style="">
<script>
new TWTR.Widget({
  version: 2,
  type: 'profile',
  rpp: 4,
  interval: 30000,
  width: 600,
  height: 300,
  theme: {
    shell: {
      background: '#333333',
      color: '#999999'
    },
    tweets: {
      background: '#000000',
      color: '#6b6b6b',
      links: '#ffffff'
    }
  },
  features: {
    scrollbar: true,
    loop: false,
    live: false,
    behavior: 'all'
  }
}).render().setUser('BINKUpdates').start();
</script> 
</div>	
<?php
printFooter();

?>
