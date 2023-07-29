<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mf.webtools.garden</title>
    <style>
      <?php include './assets/styles.css'; ?>
    </style>
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

        $geos = [];

        $AGGREGATE_GEO = $_GET['aggregate'] ?? false;
        $URL = $_GET['url'] ?? null;

        if (is_null($URL)) {
          echo '<p>No URL was provided.</p>';
          exit;
        }

        // Now all the functions documented below are available, for example:
        $mf = Mf2\fetch($URL);

        function recursivelyGetHReview ($mf2) {
          $hreviews = [];
          if (isset($mf2['type']) && in_array('h-review', $mf2['type'])) {
            return [$mf2];
          }
          foreach ($mf2['items'] as $microformat) {
            if (in_array('h-review', $microformat['type'])) {
              array_push($hreviews, $microformat);
            } else {
              if (isset($microformat["children"])) {
                foreach ($microformat["children"] as $child) {
                  $hreviews = array_merge($hreviews, recursivelyGetHReview($child));
                }
              }
            }
          }
          return $hreviews;
        }

        function getLatLongs ($reviews) {
          $geos = [];
          foreach ($reviews as $review) {
            if (isset($review["children"])) {
              foreach ($review["children"] as $child) {
                if (in_array("h-geo", $child['type'])) {
                  $geo = $child['properties'];
                  $lat = $geo['latitude'][0];
                  $long = $geo['longitude'][0];
                  array_push($geos, [$lat, $long]);
                }
              }
            }
          }
          return $geos;
        }

        function getCategories ($reviews) {
          $categories = [];
          foreach ($reviews as $review) {
              // get category
              if (!isset($review['properties']['category'])) {
                continue;
              }
              $property_categories = $review['properties']['category'];
              foreach ($property_categories as $category) {
                if (!in_array($category, $categories)) {
                  array_push($categories, $category);
                }
              }
          }
          return $categories;
        }

        $hreviews = recursivelyGetHReview($mf);
        $lat_longs = getLatLongs($hreviews);
        $categories = getCategories($hreviews);
        $first_lat_long_in_each_category = [];

        foreach ($categories as $category) {
          foreach ($hreviews as $review) {
            if (isset($review["children"])) {
              foreach ($review["children"] as $child) {
                if (in_array("h-geo", $child['type'])) {
                  $geo = $child['properties'];
                  $lat = $geo['latitude'][0];
                  $long = $geo['longitude'][0];
                  if (!isset($first_lat_long_in_each_category[$category]) && in_array($category, $review['properties']['category'])) {
                    $first_lat_long_in_each_category[$category] = [$lat, $long];
                  }
                }
              }
            }
          }
        }

        if ($AGGREGATE_GEO) {
          echo '
            <h1>Map</h1>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
            crossorigin=""/>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
            <div id="mapid" style="height: 400px;"></div>
            <ul id="categories">
            <li><a href="#" onclick="resetMapFocus(\'All\', ' . $lat_longs[0][0] . ', ' . $lat_longs[0][1] . ')">All</a></li>';
          foreach ($categories as $category) {
            echo '<li>
            <a href="#" onclick="focusOnCategory(\'' . $category . '\', ' . $first_lat_long_in_each_category[$category][0] . ', ' . $first_lat_long_in_each_category[$category][1] . ')">' . $category . '</a></li>';
          }
          echo '</ul><script>
            var mymap = L.map("mapid").setView([' . $lat_longs[0][0] . ', ' . $lat_longs[0][1] . ']
            , 13);

            var OpenStreetMap_Mapnik = L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
              maxZoom: 19,
              attribution: "&copy; <a href=https://www.openstreetmap.org/copyright>OpenStreetMap</a> contributors"
            });

            OpenStreetMap_Mapnik.addTo(mymap);

            function resetMapFocus(lat, long) {
              mymap.setView([lat, long], 13);
            }

            function focusOnCategory(category, lat, long) {
              var categoryList = document.getElementsByClassName("category-listing");
              for (var i = 0; i < categoryList.length; i++) {
                var categoryElement = categoryList[i];
                if (category === "All") {
                  categoryElement.style.display = "block";
                } else {
                  if (categoryElement.id === category) {
                    categoryElement.style.display = "block";
                  } else {
                    categoryElement.style.display = "none";
                  }
                }
              }
              console.log(lat, long);
              resetMapFocus(lat, long);
            }
          ';
          foreach ($lat_longs as $lat_long) {
            echo 'L.marker([' . $lat_long[0] . ', ' . $lat_long[1] . ']).addTo(mymap);';
          }
          echo '</script>';
        }

        if (is_array($mf)) {
          foreach (recursivelyGetHReview($mf) as $microformat) {
            $unspecified_properties = [];
            $review = $microformat['properties'];
            if (count($review) === 0) {
             echo '<p>No reviews were found on the target URL.</p>';
            }
            if (in_array('h-review', $microformat['type'])) {
              if (isset($microformat["children"])) {
                foreach ($microformat["children"] as $child) {
                  if (in_array("h-geo", $child['type'])) {
                    $geo = $child['properties'];
                    $lat = $geo['latitude'][0];
                    $long = $geo['longitude'][0];
                    if (!$AGGREGATE_GEO) {
                      echo '<iframe style="border: none" src="https://www.openstreetmap.org/export/embed.html?bbox=' . $long . '%2C' . $lat . '%2C' . $long . '%2C' . $lat . '&amp;layer=mapnik&amp;marker=' . $lat . '%2C' . $long . '" style="border: 1px solid black"></iframe><br/><small><a href="https://www.openstreetmap.org/?mlat=' . $lat . '&amp;mlon=' . $long . '#map=19/' . $lat . '/' . $long . '">View Larger Map</a></small>';
                    }
                  }
                }
              }
              if (!$AGGREGATE_GEO) {
                echo '<h1 class="p-name">' . $review['name'][0] . '</h1>';
    
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
        }
        if (count($categories) == 0) {
          $categories = ['Reviews'];
        }
        foreach ($categories as $category) {
          echo "<div class='category-listing' id='" . $category . "'>";
          echo "<h2>" . $category . "</h2>";
          echo "<ul>";
          $firstReviewInCategory = null;
          foreach ($hreviews as $review) {
            if (isset($review['properties']['category'])) {
              if (!in_array($category, $review['properties']['category']) || $category == 'Reviews') {
                continue;
              }
            }
            if (isset($review['properties']['url'])) {
              echo '<li class="p-name"><a href="' . $review['properties']['url'][0] . '" class="u-url">' . $review['properties']['name'][0] . '</a> (' . $review["properties"]["rating"][0] . '/5)</li>';
            } else {
              echo '<li class="p-name">' . $review['properties']['name'][0] . '</li>';
            }
            $last_lat_long = null;
            if (isset($review["children"])) {
              foreach ($review["children"] as $child) {
                if (in_array("h-geo", $child['type'])) {
                  $geo = $child['properties'];
                  $lat = $geo['latitude'][0];
                  $long = $geo['longitude'][0];
                  $last_lat_long = [$lat, $long];
                }
              }
            }
          }
          echo "</ul>";
          echo "</div>";
        }
        ?>
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