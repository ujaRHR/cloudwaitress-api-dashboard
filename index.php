<?php
$url = "https://api.cloudwaitress.com/v1/orders";

// API key and Restaurant ID goes here...
$API_KEY = "YOUR_API_KEY_HERE";
$RES_ID = "RES*****************";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
  "Content-Type: application/json",
  "Authorization: $API_KEY"
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = <<<DATA
{
      "restaurant_id": "$RES_ID",
      "limit": 20,
      "page": 1,
      "sort": { "created": -1 }
    }
DATA;

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

$response = curl_exec($curl);
curl_close($curl);
$data = json_decode($response);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | CloudWaitress</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fira+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
</head>

<body>
  <style scoped>
    table {
      font-family: 'Fira Mono', monospace;
    }

    .table .thead-light th {
      color: #000000;
      background-color: #d5c3ff;
      border-color: #000000;
    }

    .table td,
    .table th {
      vertical-align: middle;
      
    }

    .status-btn {
      color: #000000;
      font-weight: 700;
    }
  </style>
  <table class="table table-bordered">
    <?php
    // Current time of Los Angeles/UTC-7 in AM/PM format, 12 hours, AM/PM will be shown
    $date = new DateTime("now", new DateTimeZone("America/Los_Angeles"));
    $date = $date->format('h:i:s A');
    echo "<p style='color: #4f62c8; text-align: center; margin-top: 10px; font-weight: 700; font-size: 20px; font-family: Fira Mono, monospace;' class='font-monospace' id='time-now'> Current Time<i class='bi bi-arrow-right-short'></i>$date</p>";
    ?>
    <script>
      setInterval(function() {
        // Current time of Los Angeles/UTC-7 in AM/PM format, 12 hours, AM/PM will be shown
        var date = new Date().toLocaleString("en-US", {
          timeZone: "America/Los_Angeles",
          hour: 'numeric',
          minute: 'numeric',
          second: 'numeric',
          hour12: true
        });
        document.getElementById('time-now').innerHTML = "Current Time<i class='bi bi-arrow-right-short'></i>" + date;
      }, 1000);
    </script>

    <thead class="thead-light">
      <tr>
        <th scope="col">Name</th>
        <th scope="col">Order ID</th>
        <th scope="col">Created</th>
        <th scope="col">Type</th>
        <th scope="col">Due Time</th>
        <th scope="col">Due Passed</th>
        <th scope="col">Status</th>
        <th scope="col">Forward</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Restaurant 1 Code Starts Here
      for ($i = 0; $i < count($data->items); $i++) {
        $status = $data->items[$i]->status;
        // If status is complete then no need to show the row
        if ($status == "complete" || $status == "cancelled") {
          continue;
        } else {
          $delivery_type = $data->items[$i]->config->service;
          $selection_mode = $data->items[$i]->config->due;
          $current_time = time() * 1000;
          $created_time = $data->items[$i]->created;
          // Defining pickup_time and delivery_time
          if ($selection_mode == "now") {
            $pickup_time = $created_time + 1800000;
            $delivery_time = $created_time + 1205000;
          } else {
            $pickup_time = $data->items[$i]->config->timestamp;
            $delivery_time = ($data->items[$i]->config->timestamp) - 600000;
          }

          // Pickup Orders - If status is not ready within pickup_time then show the row red
          if ($delivery_type == "pickup" && $current_time > $pickup_time) {
            if ($status == "ready" || $status == "on_route") {
              $ready_time = $data->items[$i]->ready_in->timestamp;
              if ($ready_time > $pickup_time) {
                echo "<tr style='background: #f69e9ed1; box-shadow: 4px 2px 4px 5px #d99292;'>";
              } else {
                echo "<tr style=' '>";
              }
            } else {
              echo "<tr style='background: #f69e9ed1; box-shadow: 4px 2px 4px 5px #d99292;'>";
            }
          }

          // Delivery Orders - If status is not ready or on_route within 20/21 mins then show the row red
          else if ($delivery_type == "delivery" && $current_time > $delivery_time) {
            if ($status == "ready" || $status == "on_route") {
              $ready_time = $data->items[$i]->ready_in->timestamp;
              if ($ready_time > $delivery_time) {
                echo "<tr style='background: #f69e9ed1; box-shadow: 4px 2px 4px 5px #d99292;'>";
              } else {
                echo "<tr style=' '>";
              }
            } else {
              echo "<tr style='background: #f69e9ed1; box-shadow: 4px 2px 4px 5px #d99292;'>";
            }
          }
          // Normal row if the order is not late yet
          else {
            echo "<tr style=' '>";
          }

          // Name Column
          echo "<th scope='row'>{$data->items[$i]->customer->name}</th>";

          // Order Number Column
          $order_url = $data->items[$i]->web_url;
          echo "<td><a target='_new' style='font-weight: 700; color: #000000' href='$order_url'>#{$data->items[$i]->number}</a></td>";

          // Order Placed Column
          $timestamp_ms = $data->items[$i]->created;
          $timezone     = new DateTimeZone('America/Los_Angeles');
          $date         = new DateTime();
          $date->setTimestamp(floor($timestamp_ms / 1000));
          $date->setTimezone($timezone);
          $time_12_hour_format = $date->format('h:i A');
          echo "<td>$time_12_hour_format</td>";

          // Order Type Column
          $delivery = ucwords($data->items[$i]->config->service);
          echo "<td>{$delivery}</td>";

          // Due Time Column
          if ($selection_mode == "now") {
            // Due Time = Order Placed Time + 30 mins in AM/PM format
            $due_time = "ASAP " . date('h:i A', strtotime($time_12_hour_format . ' + 30 minutes'));
          } else if ($selection_mode == "later") {
            // When user specified a later pickup time
            $due_time = $data->items[$i]->config->time;
            $due_time = "Due &nbsp" . date('h:i A', strtotime($due_time));
          } else {
            $due_time = "N/A";
          }
          echo "<td>$due_time</td>";

          // Due Passed Column
          if ($delivery_type == "delivery" && $current_time > $delivery_time) {
            if ($status == "unconfirmed" || $status == "confirmed" || $status == "awaiting_payment") {
              $delivery_due_passed = $current_time - $delivery_time;
              $delivery_due_passed = floor($delivery_due_passed / 1000 / 60);
              echo "<td>$delivery_due_passed mins</td>";
            } else if ($status == "ready" || $status == "on_route") {
              $ready_time = $data->items[$i]->ready_in->timestamp;
              if ($ready_time > $delivery_time) {
                $delivery_due_passed = $ready_time - $delivery_time;
                $delivery_due_passed = floor($delivery_due_passed / 1000 / 60);
                echo "<td>$delivery_due_passed mins</td>";
              } else {
                echo "<td>$nbsp</td>";
              }
            }
          } else if ($delivery_type == "pickup" && $current_time > $pickup_time) {
            if ($status == "unconfirmed" || $status == "confirmed" || $status == "awaiting_payment") {
              $pickup_due_passed = $current_time - $pickup_time;
              $pickup_due_passed = floor($pickup_due_passed / 1000 / 60);
              echo "<td>$pickup_due_passed mins</td>";
            } else if ($status == "ready" || $status == "on_route") {
              $ready_time = $data->items[$i]->ready_in->timestamp;
              if ($ready_time > $pickup_time) {
                $pickup_due_passed = $ready_time - $pickup_time;
                $pickup_due_passed = floor($pickup_due_passed / 1000 / 60);
                echo "<td>$pickup_due_passed mins</td>";
              } else {
                echo "<td>$nbsp</td>";
              }
            }
          } else {
            echo "<td>$nbsp</td>";
          }

          // Status Column
          if ($status == "unconfirmed") {
            $button_class = 'btn btn-danger btn-sm';
            $button_text_color = "style='color: #ffffff'";
          } else if ($status == "confirmed") {
            $button_class = 'btn btn-warning btn-sm';
            $button_text_color = "style='color: #000000'";
          } else if ($status == "ready") {
            $button_class = 'btn btn-success btn-sm';
            $button_text_color = "style='color: #ffffff'";
          } else if ($status == "on_route") {
            $button_class = 'btn btn-info btn-sm';
            $button_text_color = "style='color: #ffffff'";
          }

          $status = ucwords($status);
          echo "<td><button $button_text_color type='button' class='status-btn $button_class'>$status</button></td>";

          // Forwards Column
          echo "<td>
                  <form method='post' action=''>
                      <input type='hidden' name='order_id$i' value='{$data->items[$i]->_id}'>
                      <input type='hidden' name='res_id$i' value='{$data->items[$i]->restaurant_id}'>
                      <input type='hidden' name='status$i' value='{$data->items[$i]->status}'>
                      <input type='hidden' name='delivery_type$i' value='{$data->items[$i]->config->service}'>
                      <button style='border: 0px solid #162596; background-color: white;' id='$i' name='submit' type='submit' value='$i'> <i class='bi bi-arrow-right-square-fill'></i></button>
                  </form>
                </td>";
        }
      }
      ?>
      </tr>
    </tbody>
  </table>
  <!-- <script language="javascript">
    setTimeout(function() {
      window.location.reload(1);
    }, 30000);
  </script> -->
</body>

</html>


<?php

//Updating Status Information Request through Curl
if (isset($_POST['submit'])) {
  $url = "https://api.cloudwaitress.com/v1/order/update-status";

  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

  $headers = array(
    "Content-Type: application/json",
    "Authorization: $API_KEY",
  );
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  // Get Restaurant ID and Order ID from the form
  $button_id = $_POST['submit'];
  $res_id = $_POST['res_id' . $button_id];
  $order_id = $_POST['order_id' . $button_id];
  $status = $_POST['status' . $button_id];
  $delivery_type = $_POST['delivery_type' . $button_id];

  // If delivery_type is delivery, then available statuses are unconfirmed, confirmed, ready, on_route, complete serially
  // If delivery_type is pickup, then available statuses are unconfirmed, confirmed, ready, complete serially
  // based on current status, update the status to the next status as per the above conditions
  if ($delivery_type == "delivery") {
    if ($status == "unconfirmed") {
      $status = "confirmed";
    } else if ($status == "confirmed") {
      $status = "ready";
    } else if ($status == "ready") {
      $status = "on_route";
    } else if ($status == "on_route") {
      $status = "complete";
    }
  } else if ($delivery_type == "pickup") {
    if ($status == "unconfirmed") {
      $status = "confirmed";
    } else if ($status == "confirmed") {
      $status = "ready";
    } else if ($status == "ready") {
      $status = "complete";
    }
  }


  $output = <<<DATA
      {
        "restaurant_id": "$res_id",
        "order_id": "$order_id",
        "status": "$status"
      }
    DATA;

  curl_setopt($curl, CURLOPT_POSTFIELDS, $output);
  $resp = curl_exec($curl);
  curl_close($curl);
  // Reload the page one time as soon as the status is updated
  echo "<meta http-equiv='refresh' content='0'>";
}
// End of Custom PHP-Curl Code //