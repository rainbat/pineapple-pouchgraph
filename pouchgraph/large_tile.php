</br></br><div class="my_large_tile_content">

<?php

global $directory, $rel_dir, $version, $name;
require($directory."includes/vars.php");

?>

<script type='text/javascript' src='/components/infusions/pouchgraph/includes/js/pouchdb-3.6.0.min.js'></script>
<script type='text/javascript' src='/components/infusions/pouchgraph/includes/js/infusion.js'></script>
<script type='text/javascript' src='/components/infusions/pouchgraph/includes/js/dist/vivagraph.js'></script>

<style>@import url('/components/infusions/pouchgraph/includes/css/infusion.css')</style>

<div class=sidePanelTitle><?php echo $name; ?> - v<?php echo $version; ?>&nbsp;<span id="PouchGraph" class="refresh_text"></span></div>
<div class=sidePanelContent>
<div id="PouchGraph" class="tab">

<br/>

<script type="text/javascript">
     
    var ENTER_KEY = 13;      
    var remoteCouch = 'http://espresso.appuccino.ch:5984/pinehack';
    var db = new PouchDB( remoteCouch, {
      ajax: {
        headers: {
          Authorization: 'Basic ' + btoa('dom:domdom')
        }
      }
    });
    db.changes({
        since: 'now',
        live: true
    }).on('change', logchange );


    function logchange(){
        console.log('sync');
    }

    function sync() {
      console.log("sync");
      var opts = {live: true};
      db.replicate.to(remoteCouch, opts, syncError);
      db.replicate.from(remoteCouch, opts, syncError);
    }

    function truncate(){

      PouchDB.destroy('test2').then(function() { 
        var db = new PouchDB('test');
      });
    }

    function syncError() {
      //console.log("sync error");
    }

    function parseKarma(){  

        $.get("/components/system/pineap/functions.php?action=get_log&_csrfToken=<?php echo $_SESSION["_csrfToken"]; ?>",function(data){

            var perLine=data.split("\n");
            var myVars=[];
            
            for(i=0;i<perLine.length-1;i++){
                var line=perLine[i].split(" ");
                ptime = line[3];
                ptype = line[4];
                pmac = line[7];
                pssid = line[10];
                if(ptype == "Probe"){
                    if( pssid != null ){
                      pssid = pssid.replace("&#039;","");
                      pssid = pssid.replace("&#039;","");
                    }
                    var n = {};
                    n.name = pmac;
                    n.group = 1;

                    var ti = new Date().toISOString();
                    var key = perLine[i]; //ti;
                    var probe = {
                      _id: key,
                      time: ti,
                      type: 'probe',
                      pssid: pssid,
                      pmac: pmac
                    };
                    db.put(probe, function callback(err, result) {
                      if (!err) {
                        console.log('Successfully posted a probe!');
                      }
                    });
                }    
          };

          sync();
            
        });

    }


    var graph;
    var colors = [
            "#1f77b4", "#aec7e8", 
            "#ff7f0e", "#ffbb78",
            "#2ca02c", "#98df8a",
            "#d62728", "#ff9896",
            "#9467bd", "#c5b0d5",
            "#8c564b", "#c49c94",
            "#e377c2", "#f7b6d2",
            "#7f7f7f", "#c7c7c7",
            "#bcbd22", "#dbdb8d",
            "#17becf", "#9edae5"
            ];
              

    graph = Viva.Graph.graph();
    graph.Name = "Sample graph from d3 library";
    graph.addNode( "pine" , "Pine" );
      
    function drawMe(){

        db.allDocs({
          include_docs: true, 
          attachments: true
        
        }).then(function (result) {
    
            console.log( "..." + result.total_rows );
            
            for(var k in result.rows) {

              pmac = result.rows[k].doc.pmac;

              pssid = result.rows[k].doc.pssid;
              
              var search = $('#search').val()
              
              if( pmac.indexOf( search) > -1 || pssid.indexOf( search ) > -1 ){

                var n = {};
                n.name = pmac;
                n.group = 1;

                //Add Device
                graph.addNode(pmac, n);
                graph.addLink("pine", pmac , 2); 
                
                var m = {};
                m.name = pssid;
                m.group = 2;
                //Add probed SSID
                graph.addNode(pssid, m);
                graph.addLink(pmac, pssid , 2);
              
              }
        
            }

        }).catch(function (err) {
        
          console.log(err);
        
        });

        initRender();
    }




        function initRender(){

            var layout = Viva.Graph.Layout.forceDirected(graph, {
                springLength : 65,
                springCoeff : 0.00055,
                dragCoeff : 0.09,
                gravity : -100
            });

            var svgGraphics = Viva.Graph.View.svgGraphics();
            svgGraphics.node(function(node){
                var groupId = node.data.group;
                var circle = Viva.Graph.svg("circle")
                    .attr("r", 25)
                    .attr("stroke", "#fff")
                    .attr("stroke-width", "1.5px")
                    .attr("fill", colors[groupId ? groupId - 1 : 5]);
                circle.append("title").text(node.data.name);
                var nodeSize = 24;
                var ui       = Viva.Graph.svg("g");
                var svgText  = Viva.Graph.svg("text").attr("y", "-4px").text(node.id);
                var      img = Viva.Graph.svg("image")
                               .attr("width", nodeSize)
                               .attr("height", nodeSize);
                ui.append(circle);
                ui.append(svgText);
                ui.addEventListener("click", function () {
                    layout.pinNode(node, !layout.isNodePinned(node));
                });
                ui.addEventListener("drag", function () {
                    layout.pinNode(node, !layout.isNodePinned(node));
                });
                return ui;
            }).placeNode(function(nodeUI, pos){
                //nodeUI.attr( "cx", pos.x).attr("cy", pos.y);               
                var nodeSize = 24;
                nodeUI.attr("transform", "translate(" + (pos.x - nodeSize/2) + "," + (pos.y - nodeSize/2) + ")");
            });
            svgGraphics.link(function(link){
                return Viva.Graph.svg("line")
                        .attr("stroke", "#999")
                        .attr("stroke-width", Math.sqrt(link.data));
            });
            var renderer = Viva.Graph.View.renderer(graph, {
                container : document.getElementById("graph1"),
                layout : layout,
                graphics : svgGraphics,
                prerender: 20,
                renderLinks : true
            });
            renderer.run(500);
        }


        </script>
        <style type='text/css'>
            #graph1{
                position: fixed;
                vertical-align:middle;
                width: 100%;
                height: 100%;
                background-color: white;
                top: 0px;
                left: 0px;
            }
            #graph1 > svg {
                width: 100%;
                height: 100%;
            }
        </style>
        <div id="graph1">
         <br/>
         <br/>
         <br/>
         <br/>
         <br/>
         <button onClick="parseKarma()">ParseKarma</button>
         <button onClick="drawMe()">GraphProbes</button>
         <input type=text id=search />>
         <button onClick="window.location ='index.php';">x</button>
        </div>

</div>




<!-- .link("https://secure.gravatar.com/avatar/rainbat"); -->