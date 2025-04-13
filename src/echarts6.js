function echarts6_(event, container, page, echartQuery) {
  //console.log(statements);
  let data = getStatementsSelection("", page, echartQuery);
  if (typeof data !== "undefined" && data.length > 0)
    echartSetup(container, data, echartQuery);
  else {
    userAlerts("nodatamodal");
    return;
  }
}

// function: get statements
function getStatementsSelection(verb, page, echartQuery) {
  // query relevant statements in ILIAS storage object and get selection
  const st = JSON.parse(JSON.stringify(statements));


  console.log(statements);
  let selection = new ADL.Collection(st);
  selection
    .where(
//      'actor.account != "undefined" and actor.account.name != "6@b4b4f485-9c96-4593-bb0b-9674d0840834.ilias" and (verb.id = "http://adlnet.gov/expapi/verbs/completed" or verb.id = "http://adlnet.gov/expapi/verbs/answered" or verb.id = "http://adlnet.gov/expapi/verbs/experienced" or verb.id = "http://adlnet.gov/expapi/verbs/failed")'
  'actor.account != "undefined" and (verb.id = "http://adlnet.gov/expapi/verbs/completed" or verb.id = "http://adlnet.gov/expapi/verbs/answered" or verb.id = "http://adlnet.gov/expapi/verbs/experienced" or verb.id = "http://adlnet.gov/expapi/verbs/failed")'
    )
    .orderBy("timestamp")
    .exec(function (data) {
      for (var i = 0; i < data.length; i++) {
        if (
          (statusChangedTo.length > 0 &&
            moment(data[i].timestamp).isAfter(statusChangedTo)) ||
          (statusChangedFrom.length > 0 &&
            moment(data[i].timestamp).isBefore(statusChangedFrom))
        )
          delete data[i];
        else {
          data[i]["name"] = data[i].actor.account.name;
          data[i]["day"] = moment(data[i].timestamp).format("YYYY-MM-DD");
          data[i]["objname"] = data[i].object.definition.name["en-US"];
          if (data[i].object.id.includes("h5p_object_id"))
            data[i]["h5p"] = data[i].object.id.split("h5p_object_id=")[1];
          else data[i]["object"] = data[i].object.id.split("obj_id_lrs=")[1];
          data[i]["month"] = moment(data[i].timestamp).format("YYYY-MM");
          data[i]["verb"] = data[i].verb.display["en-US"];
          if (data[i].result) {
            if (data[i].result.score)
              data[i]["scaled"] = data[i].result.score.scaled;
            if (data[i].result.success)
              data[i]["success"] = data[i].result.success;
            else data[i]["success"] = false;
            if (data[i].context.contextActivities.grouping.length < 1) {
              data[i]["courseduration"] = moment
                .duration(data[i].result.duration)
                .asMinutes();
            } else if (data[i].result.duration)
              data[i]["duration"] = moment
                .duration(data[i].result.duration)
                .asMinutes();
          }
          delete data[i].version;
          delete data[i].actor;
          delete data[i].stored;
          delete data[i].authority;
          delete data[i].context;
          delete data[i].result;
          //delete data[i].verb;
          delete data[i].actor;
          delete data[i].id;
        }
      }
      return selection;
    });
  console.log(selection.contents);
  return selection.contents;
}
// function: prepare option object
function echartSetup(container, data_) {
  if (document.getElementById(container))
    container = document.getElementById(container);

  let myChart = echarts.init(container),
    option,
    series = [],
    pieData = [],
    selH5pStatus = new ADL.Collection(JSON.parse(JSON.stringify(data_))),
    selH5pCount = new ADL.Collection(JSON.parse(JSON.stringify(data_))),
    selGeneralStatusScaled = new ADL.Collection(JSON.parse(JSON.stringify(data_)));

  // arrange data on teststatus
  selGeneralStatusScaled
    .where("(verb = 'completed' or verb = 'failed')")
    .groupBy("name")
    .groupBy("success")
    .median("scaled", 1)
    .select(
      "group as name, data[0].group as success, data[0].median as median, data[0].data[0].objname as objname"
    );
  selGeneralStatusScaled = selGeneralStatusScaled.contents;
  console.log("selGeneralStatusScaled");
  console.log(selGeneralStatusScaled);

  // arrange data on h5p status
  selH5pStatus
    .where("verb = 'answered'")
    .groupBy("name")
    .groupBy("h5p")
    .median("scaled", 1)
    .math("objname", "( $(data[0].objname) )", 1);
  selH5pStatus = selH5pStatus.contents;
  console.log("selH5pStatus");
  console.log(selH5pStatus);

  // arrnage data on h5p objects
  selH5pCount.groupBy("h5p").select("group, data[0].objname as objname");
  selH5pCount = selH5pCount.contents;
  console.log("selH5pCount");
  console.log(selH5pCount);

  // populate pie data
  let pieValues = [],
    complete = 1,
    incomplete = 1;
  for (let i = 0; i < selGeneralStatusScaled.length; i++) {
    if (selGeneralStatusScaled[i].success === "true") {
      pieValues[0] = {
        value: complete++,
        name: "Complete",
        itemStyle: { color: "green" },
      };
    } else {
      pieValues[1] = {
        value: incomplete++,
        name: "Incomplete",
        itemStyle: { color: "red" },
      };
    }
  }
  for (let i = 0; i < pieValues.length; i++) {
    pieData.push(pieValues[i]);
  }

  // populate bar data
  let objectLabels = ["objects"];
  for (let i = 0; i < selH5pCount.length; i++) {
    objectLabels = [...objectLabels, selH5pCount[i].objname];
  }
  console.log(objectLabels);

  let h5pObjects = [];
  for (let i = 0; i < selH5pStatus.length; i++) {
    h5pObjects[i] = [];
    h5pObjects[i] = [...h5pObjects[i], "User " + (i + 1)];
    for (let j = 0; j < selH5pCount.length; j++) {
      if (
        selH5pStatus[i].data[j] &&
        !Array.isArray(selH5pStatus[i].data[j].median) &&
        Number(selH5pStatus[i].data[j].median > 0)
      ) {
        h5pObjects[i] = [
          ...h5pObjects[i],
          Number(selH5pStatus[i].data[j].median.toFixed(1)) * 100,
        ];
      } else {
        h5pObjects[i] = [...h5pObjects[i], null];
      }
    }
  }
  h5pObjects = [objectLabels, ...h5pObjects];
  //console.log("h5pObjects");
  //console.log(h5pObjects);

  // add pie to series
  series.push({
    name: "Kurs Status",
    type: "pie",
    radius: [0, 70],
    center: ["87%", "27%"],
    itemStyle: {
      borderRadius: 5,
    },
    label: {
      show: true,
    },
    emphasis: {
      label: {
        show: true,
      },
    },
    data: pieData,
  });

  for (let i = 0; i < selH5pCount.length; i++) {
    series.push({
      type: "bar",
      label: {
        show: true,
        //formatter: "%",
      },
      itemStyle: {
        borderWidth: 1,
        borderType: "solid",
        borderColor: "#fff",
      },
      stack: "stack",
      tooltip: {
        show: true,
        //formatter: "<strong>{b}</strong><br />{a}: %",
      },
    });
  }

  // add series to option
  option = {
    title: {
      text: "Bearbeitungsstand im Kurs",
      subtext:
        "Hinweis: Der Bearbeitungsstand wird immer von Anfang bis zum eingestellten Datum ermittelt",
      left: 78,
      marginLeft: "20px",
    },
    // CTO, text field as button
    graphic: [
      {
        type: "group",
        right: 20,
        bottom: 20,
        children: [
          {
            type: "rect",
            z: 100,
            left: "center",
            top: "middle",
            shape: {
              width: 200,
              height: 80,
            },
            style: {
              fill: "#eee",
              stroke: "#555",
              lineWidth: 1,
              shadowBlur: 8,
              shadowOffsetX: 3,
              shadowOffsetY: 3,
              shadowColor: "rgba(0,0,0,0.2)",
            },
          },
          {
            type: "text",
            z: 100,
            left: "center",
            top: "middle",
            style: {
              fill: "#333",
              width: 180,
              overflow: "break",
              text: 'Weiter zur Monatsübersicht "Teilnehmer pro Tag"',
              font: "14px Calibri",
            },
          },
        ],
      },
    ],
    toolbox: {
      show: true,
      feature: {
        dataView: {
          readOnly: false,
        },
        magicType: {
          type: ["bar", "stack"],
        },
        restore: {},
        saveAsImage: {},
      },
    },
    legend: {
      orient: "vertical",
      left: "75%",
      top: "48%",
      type: "scroll",
      data: objectLabels
    },
    tooltip: {
      //trigger: "axis",
      axisPointer: {
        type: "shadow",
      },
    },
    grid: {
      left: "3%",
      right: "27%",
      bottom: "3%",
      top: "12%",
      containLabel: true,
    },
    yAxis: [
      {
        type: "category",
        //minInterval: 1,
        axisTick: {
          alignWithLabel: true,
        },
      },
    ],
    xAxis: [
      {
        //type: "value",
        //minInterval: "",
      },
    ],
    dataset: {
      source: h5pObjects,
    },
    series: series,
  };
  //console.log(option.dataset);
  // instantiate option object
  if (option && typeof option === "object") {
    myChart.setOption(option);
  }
  setTimeout(() => {
    let resizeEvent = new Event("resize");
    window.dispatchEvent(resizeEvent);
  }, 0);
  window.addEventListener("resize", myChart.resize);

  var zoomSize = 5,
    click = true,
    sv,
    ev;
  myChart.on("click", function (params) {
    //console.log(params);
    if (params.componentType === "graphic") {
      let c = document.getElementById("container2");
      if (c.classList.contains("hide")) {
        if (!c.innerHTML) {
          loadScript("echarts61.js", function () {
            echarts61_("", "container2", data_);
          });
        }
        c.classList.remove("hide");
        c.classList.add("show");
      } else {
        c.classList.remove("show");
        c.classList.add("hide");
      }
      if (click) {
        click = false;
        sv = params.value - zoomSize / 2;
        ev = params.value + zoomSize / 2;
      } else {
        click = true;
        ev = 1000;
        sv = 0;
      }
      myChart.dispatchAction({
        type: "dataZoom",
        startValue: sv,
        endValue: ev,
      });
    }
  });
  myChart.on("dblclick", function (params) {
    xMouseDown = true;
    for (let k = 0, s, o; k < selSuccess.length; k++) {
      o = selSuccess[k].group;
      if (o.includes(params.name) && o.includes("objectid/")) {
        s =
          "https://" +
          o.substring(
            o.indexOf("objectid/") + "objectid/".length,
            o.indexOf("/h5pcid_", o.indexOf("objectid/"))
          );
        location.href =
          s +
          "?" +
          sessionStorage.getItem("cmi5Parms") +
          "#h5p-iframe-" +
          o.substring(
            o.indexOf("h5pcid_") + "h5pcid_".length,
            o.indexOf("/", o.indexOf("h5pcid_"))
          );
      }
    }
  });
}

function rcolor() {
  let maxVal = 0xffffff; // 16777215
  let randomNumber = Math.random() * maxVal;
  randomNumber = Math.floor(randomNumber);
  randomNumber = randomNumber.toString(16);
  let randColor = randomNumber.padStart(6, 0);
  return "#" + randColor.toUpperCase();
}
