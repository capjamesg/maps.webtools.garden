<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mf.webtools.garden</title>
    <link rel="stylesheet" href="./assets/styles.css">
  </head>
  <body>
    <main>
      <nav>
        <p>Go back to <a href="https://mf.webtools.garden">mf.webtools.garden</a></p>
      </nav>
      <article>
        <?php

        require_once 'Parser.php';

        function getValue($value) {
          if (is_array($value)) {
            return $value[0];
          } else {
            return $value;
          }
        }

        // Now all the functions documented below are available, for example:
        $mf = Mf2\fetch('https://jamesg.blog/checkins/san%20francisco/peet-s-coffee/');

        if (is_array($mf)) {
            foreach ($mf['items'] as $microformat) {
              $unspecified_properties = [];
              $review = $microformat['properties'];
              echo '<h1 class="p-name">' . $review['name'][0] . '</h1>';

              if (in_array('h-review', $microformat['type'])) {
                if (isset($microformat["children"])) {
                  foreach ($microformat["children"] as $child) {
                    if (in_array("h-geo", $child['type'])) {
                      $geo = $child['properties'];
                      $lat = $geo['latitude'][0];
                      $long = $geo['longitude'][0];
                      echo '<iframe style="border: none" src="https://www.openstreetmap.org/export/embed.html?bbox=' . $long . '%2C' . $lat . '%2C' . $long . '%2C' . $lat . '&amp;layer=mapnik&amp;marker=' . $lat . '%2C' . $long . '" style="border: 1px solid black"></iframe><br/><small><a href="https://www.openstreetmap.org/?mlat=' . $lat . '&amp;mlon=' . $long . '#map=19/' . $lat . '/' . $long . '">View Larger Map</a></small>';
                    }
                  }
                }
                // if out of 5, make star emojis
                $stars = [];
                $rating = $review['rating'][0];
                if (!isset($review['best'])) {
                  $pBest = 5;
                  array_push($unspecified_properties, 'best');
                }
                if (!isset($review['worst'])) {
                  $pWorst = 1;
                  array_push($unspecified_properties, 'worst');
                }
                for ($i = 0; $i < $rating; $i++) {
                  array_push($stars, '⭐️');
                }
                echo '<p class="p-rating">' . implode($stars) . ' out of ' . $pBest . '</p>';
                if (isset($pWorst)) {
                  echo '<p class="e-content">' . getValue($review['content'][0]["html"]) . '</p>';
                }
              }
            }
          }
        ?>
        <p>Categories: <ul class="p-category full-list">
          <?php
            foreach ($review['category'] as $category) {
              echo '<li>' . $category . '</li>';
            }
          ?>
        </ul></p>
      </article>
      <details>
        <summary>Debug</summary>
        <section>
          <h2>Unspecified Properties</h2>
          <?php
            foreach ($unspecified_properties as $property) {
              echo '<p>' . $property . '</p>';
            }
          ?>
        </section>
        <section>
          <h2>Parse Tree</h2>
          <pre>
            <?php
              echo json_encode($mf, JSON_PRETTY_PRINT);
            ?>
          </pre>
        </section>
      </details>
    </main>
  </body>
</html>