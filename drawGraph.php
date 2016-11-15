<html>

<head>
    <title>Graph Sleep Data</title>
    <script src="http://d3js.org/d3.v4.min.js" charset="utf-8"></script>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Jura');
        #visualization {
            height: 500px;
            margin: 0 auto;
            position: relative;
            width: 1000px;
        }
        
        .path {
            stroke-width: .5;
        }
        
        .axis {
            shape-rendering: smoothEdges;
            stroke-width: .5;
        }
        
        .x.axis line {
            stroke: lightgrey;
        }
        
        .x.axis .minor {
            stroke-opacity: .5;
            font-family: 'Jura', sans-serif;
        }
        
        .x.axis path {
            display: none;
        }
        
        .axis text {
            color: dimgray;
            font-family: 'Jura', sans-serif;
            font-size: 14;
        }
        
        .label {
            font-family: 'Jura', sans-serif;
            font-size: 18;
        }
        
        .y.axis line,
        .y.axis path {
            fill: none;
            stroke: #000;
        }
        
        .grid .tick {
            stroke: lightgrey;
            stroke-opacity: 0.7;
            shape-rendering: crispEdges;
        }
        
        .grid path {
            stroke-width: 0;
        }
        
        .legend {
            font-family: sans-serif;
            font-size: 12px;
        }
        
        rect {
            stroke-width: 2;
        }
        
        .overlay {
            opacity:0;
            pointer-events: all;
        }
        
        .focus circle {
            fill: none;
        }
        
        .focus text {
            font-family: 'Jura', sans-serif;
            font-size: 14;
            color: dimgray;
        }
        
        h1 {
            text-align: center;
            font-family: 'Jura', sans-serif;
            font-size: 36;
        }
    </style>
</head>

<body>
    <div style="text-align:center; width:1000px; vertical-align:baseline"><h1>&nbsp;Graph of sleep data</h1></div>

    <?php
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "iot_data";
            $tablename = "accel_data";
            $data_array = array();
        
            $connection = new mysqli($servername, $username, $password, $dbname);
            if($connection->connect_error){
                die("Connection failed: ".$connection->connect_error);
            } 
            $sleep_id = isset($_REQUEST["sleep"]) ? (int) $_REQUEST["sleep"] : die("\"sleep\" is not specified...<br>Aborting...");
            
            $sql = "SELECT `timestamp`,`cal_acc_x`, `cal_acc_y`, `cal_acc_z` FROM $tablename WHERE sleep_id=$sleep_id";

            $result = $connection->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $data = new StdClass;
                    $data->timestamp = $row["timestamp"];
                    $data->x = (double)$row["cal_acc_x"] * 1000;
                    $data->y = (double)$row["cal_acc_y"] * 1000;
                    $data->z = (double)$row["cal_acc_z"] * 1000;
                    $data_array[] = $data;
                }
            } else {
                die("No data for sleep=".$sleep_id);
            }
            $connection->close();
    ?>

        <svg id="visualisation"></svg>

        <script>
            var WIDTH = 1000,
                HEIGHT = 500,
                MARGINS = {
                    top: 20,
                    right: 20,
                    bottom: 20,
                    left: 60
                };

            var vis = d3.select("#visualisation").attr("width", WIDTH).attr("height", HEIGHT);

            var data = <?php 
            echo json_encode($data_array);
        ?>;

            var format = d3.timeParse("%Y-%m-%d %H:%M:%S");
            data.forEach(function(d) {
                d.timestamp = format(d.timestamp);
                console.log(d.timestamp + ":[" + d.x + "," + d.y + "," + d.z + "]")
            });

            var xScale = d3.scaleTime().range([MARGINS.left, WIDTH - MARGINS.right]).domain(d3.extent(data, function(d) {
                return d.timestamp;
            }));
            var yScale = d3.scaleLinear().range([HEIGHT - MARGINS.top, MARGINS.bottom]).domain([d3.min(data.map(function(o) {
                return Math.min(o.x, o.y, o.z);
            })), d3.max(data.map(function(o) {
                return Math.max(o.x, o.y, o.z);
            }))]);

            var xAxis = d3.axisBottom().scale(xScale),
                yAxis = d3.axisLeft().scale(yScale);

            xAxis.tickSize(MARGINS.top + MARGINS.bottom - HEIGHT); // Vertical Grid lines

            var color = d3.scaleOrdinal()
                .range(['#BC4747', '#82BC47', '#474FBC'])
                .domain(d3.keys(data[0]).filter(function(key) {
                    return key !== "timestamp";
                }));

            console.log(JSON.stringify(color.domain()));

            var axes = color.domain().map(function(axis) {
                return {
                    axis: axis,
                    values: data.map(function(d) {
                        return {
                            timestamp: d.timestamp,
                            value: +(d[axis])
                        };
                    }),
                    visible: true
                };
            });

            console.log(JSON.stringify(color.domain()));

            var line = d3.line()
                .x(function(d) {
                    return xScale(d.timestamp);
                })
                .y(function(d) {
                    return yScale(d.value);
                });

            vis.append("svg:g")
                .attr("class", "x axis")
                .attr("transform", "translate(0," + (HEIGHT - MARGINS.bottom) + ")")
                .call(xAxis);

            vis.append("svg:g")
                .attr("class", "y axis")
                .attr("transform", "translate(" + (MARGINS.left) + ",0)")
                .call(yAxis);

            vis.append("text")
                .attr("class", "label")
                .attr("transform", "rotate(-90)")
                .attr("x", -HEIGHT / 2)
                .attr("dy", "1em")
                .style("text-anchor", "middle")
                .text("Acceleration (mm/s²)");

            vis.append("text")
                .attr("x", 101)
                .attr("y", 17)
                .attr("font-size", "16px")
                .attr("font-family", "'Jura', sans-serif")
                .attr("font-weight", "bold")
                .attr("dy", "1em")
                .style("text-anchor", "middle")
                .text("Sleep = <?php echo $sleep_id?> ");

            var accel = vis.selectAll(".accel")
                .data(axes)
                .enter().append("g")
                .attr("class", "accel");

            accel.append("path")
                .attr("class", "path")
                .attr("id", function(d) {
                    return "line-" + d.axis.toUpperCase();
                })
                .attr("d", function(d) {
                    return d.visible ? line(d.values) : null;
                })
                .attr("stroke", function(d) {
                    return color(d.axis);
                })
                .attr("fill", 'none');

            var hoverContainer, hoverLine, hoverLineGroup;

            hoverLineGroup = vis.append("svg:g")
                .attr("class", "hover-line");
            hoverLine = hoverLineGroup
                .append("svg:line")
                .attr("x1", 10).attr("x2", 10)
                .attr("y1", 0).attr("y2", HEIGHT)
                .attr('stroke', 'GREY')
                .attr('stroke-width', 0);

            hoverLine.classed("hide", true);

            var handleMouseMoveGraph = function(event) {
                    var mouseX = d3.event.layerX - 6;
                    var mouseY = d3.event.layerY - 6;

                    if (mouseX >= 60 && mouseX <= WIDTH && mouseY >= 0 && mouseY <= HEIGHT) {
                        hoverLine.classed("hide", false);
                        hoverLine.attr('stroke-width', 1);
                        hoverLine.attr("x1", mouseX).attr("x2", mouseX);

                    } else {
                        handleMouseOutGraph(event);
                    }
                } //end handleMouseOverGraph

            var handleMouseOutGraph = function(event) {
                console.log("MouseOut graph")
                hoverLine.classed("hide", true);
                hoverLine.attr('stroke-width', 0);
            }

            var focus = vis.selectAll(".focus")
                .data(color.domain().map(function(axis) {
                    return {
                        axis: axis
                    };
                }))
                .enter().append("g")
                .attr("class", "focus");

            focus.append('circle')
                .attr("r", 4.5)
                .attr("stroke", function(d) {
                    return color(d.axis);
                });

            focus.append('text')
                .attr("x", 9)
                .attr("y", -9)
                .attr("dy", ".35em");

            var bisectDate = d3.bisector(function(d) {
                return d.timestamp;
            }).left;

            var formatDate = d3.timeFormat("%a %d %b %Y   %H:%M:%S");

            var currentTimeText = vis.append("text")
                .attr("x", 800)
                .attr("y", 17)
                .attr("align", "center")
                .attr("font-size", "14px")
                .attr("font-family", "'Jura', sans-serif")
                .attr("dy", "1em")
                .attr("display", "none")
                .text("");

            function mousemove(event) {
                var mouseX = d3.event.layerX - 6,
                    mouseY = d3.event.layerY - 6,
                    x0 = xScale.invert(mouseX), //timestamp Obj
                    i = bisectDate(data, x0, 1),
                    d0 = data[i - 1],
                    d1 = data[i];
                var span = (xScale(d1.timestamp) - xScale(d0.timestamp)),
                    weight = (xScale(x0) - xScale(d0.timestamp)) / span;
                var focusPosition = function(axis) {
                    return (+(d1[axis]) * weight) + (+(d0[axis]) * (1.0 - weight));
                };

                if (mouseX >= 60 && mouseX <= WIDTH && mouseY >= 0 && mouseY <= HEIGHT) {
                    focus.attr("display", null);
                    currentTimeText.attr("display", null);

                    focus.attr("transform", function(d) {
                        return "translate(" + xScale(x0) + "," + yScale(focusPosition(d.axis)) + ")";
                    });

                    focus.select("text").text(function(d) {
                        return focusPosition(d.axis).toPrecision(3);
                    });
                    currentTimeText.text(formatDate(x0));
                    if (mouseX >= WIDTH - 80){
                        focus.select("text").attr("x", "-45");
                    } else {
                        focus.select("text").attr("x", "9");
                    }
                } else {
                    focus.attr("display", "none");
                    currentTimeText.attr("display", "none");
                }
                
                
            }

            var legendRectSize = 18;
            var legendSpacing = 4;

            var legend = vis.selectAll('.legend')
                .data(color.domain())
                .enter()
                .append('g')
                .attr('class', 'legend')
                .attr('transform', function(d, i) {
                    var height = legendRectSize + legendSpacing;
                    var offset = height * color.domain().length / 2;
                    var horz = 70;
                    var vert = i * height + 45;
                    return 'translate(' + horz + ',' + vert + ')';
                });

            legend.append('rect')
                .attr('width', legendRectSize)
                .attr('height', legendRectSize)
                .style('fill', color)
                .style('stroke', color);

            legend.append('text')
                .attr('x', legendRectSize + 1.5 * legendSpacing)
                .attr('y', legendRectSize - legendSpacing)
                .text(function(d) {
                    return d.toUpperCase();
                })
            
            var overlay = vis.append("rect")
                .attr("class", "overlay")
                .attr("width", WIDTH)
                .attr("height", HEIGHT)
                .on("mouseover", function(event) {
                    focus.style("display", null);
                    currentTimeText.style("display", null);
                })
                .on("mouseout", function(event) {
                    focus.style("display", "none");
                    currentTimeText.style("display", "none");
                    handleMouseOutGraph(d3.event);
                })
                .on("mousemove", function(event) {
                    mousemove(d3.event);
                    handleMouseMoveGraph(d3.event);
                });
            
        </script>

</body>

</html>