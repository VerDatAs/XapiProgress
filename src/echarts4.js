// function: Wer hat welche H5P-Interaktionen mit welchem Erfolg bearbeitet?

function echarts4_(event, container, page, echartQuery) {
  let data = getStatementsSelection("answered", page, echartQuery);
  if (typeof data !== "undefined" && data.length > 0)
    echartSetup(container, data, echartQuery);
  else {
    userAlerts("nodatamodal");
    return;
  }
}

// function: get success of answered statements
function getStatementsSelection(verb, page, echartQuery) {
  let stmtsQ = sessionStorage.getItem("statements"),
    since = "",
    until = "",
    stmtsQ_,
    qStored,
    data = [];
  // check if previuos data of echarts4 in sessiostorage and get data if applicable
  if (stmtsQ && stmtsQ.length > 0) {
    stmtsQ = JSON.parse(stmtsQ);
    for (let i = 0; i < stmtsQ.length; i++) {
      if (Object.keys(stmtsQ[i]).includes(echartQuery)) stmtsQ_ = stmtsQ[i];
    }
    if (stmtsQ_) {
      until = new Date();
      let l = stmtsQ_[echartQuery].length - 1;
      since = stmtsQ_[echartQuery][l]["timestamp"];
      stmtsQ_ = stmtsQ_[echartQuery];
      qStored = true;
    }
  } else stmtsQ = [];
  // query relevant statements in LRS and get selection
  let selection = bm.getStatementsBase(
    verb,
    "", //agent
    "", //registration
    "", //sessionid
    since,
    until,
    true, //relatedactivities
    true, //relatedagents
    "ids", //format
    "", //activity
    "", //page
    true, //more
    cmi5Controller.activityId, //extensionsActivityId
    echartQuery //query
  );
  // push relevant information of selected statements to data object
  var sel = new ADL.Collection(selection);
  sel
    .where("actor.account !== 'undefined' and result.score !== 'undefined'")
    .orderBy("timestamp")
    .exec(function (data) {
      for (var i = 0; i < data.length; i++) {
        data[i]["name"] = data[i].actor.account.name;
        data[i]["object"] = data[i].object.id;
        data[i]["duration"] = moment
          .duration(data[i].result.duration)
          .asMilliseconds();
        data[i]["scaled"] = data[i].result.score.scaled;
        data[i]["success"] = data[i].result.success;
        delete data[i].version;
        delete data[i].actor;
        delete data[i].stored;
        delete data[i].authority;
        delete data[i].context;
        delete data[i].result;
        delete data[i].verb;
        delete data[i].actor;
        delete data[i].id;
      }
      return sel; //.groupBy("timestamp", [t1, t2, 86400000]);
    });
  data = sel.contents;

  // push previuos data in sessiostorage of echarts4 to data object if applicable
  if (qStored) data.push(...stmtsQ_);

  // assign sorted data to cmi5Controller[echarts4] object
  if (data.length > 0) {
    if (stmtsQ.length > 0) {
      for (let i = 0; i < stmtsQ.length; i++) {
        if (Object.keys(stmtsQ[i]).includes(echartQuery))
          stmtsQ[i] = { [echartQuery]: data };
      }
    } else stmtsQ.push({ [echartQuery]: data });
    // update data in sessionstorage
    sessionStorage.setItem("statements", JSON.stringify(stmtsQ));
  }
  return data;
}

// function: draw echart
function echartSetup(container, data_, echartQuery) {
  /*
    ["name"]: actor.account.name,
    ["object"]: object.id,
    ["timestamp"]: timestamp,
    ["duration"]: result.duration,
    ["scaled"]: result.score.scaled,
    ["success"]: result.success
  */
  if (document.getElementById(container))
    container = document.getElementById(container);
  
    let myChart = echarts.init(container),
      option,
      series = [],
      pieData = [],
      color,
      success = { true: 0, false: 0 },
      users = [],
      objects = [],
      selSuccess = new ADL.Collection(data_),
      selDuration = new ADL.Collection(data_),
      selScaled = new ADL.Collection(data_),
      selUsers = new ADL.Collection(data_);

    selSuccess
      .groupBy("object")
      .groupBy("name")
      .groupBy("success")
      .count(1)
      .relate("group", "count");

    selDuration
      .groupBy("object")
      .groupBy("name")
      .sum("duration", 1)
      .relate("group", "sum");

    selScaled
      .groupBy("object")
      .groupBy("name")
      .average("scaled", 1)
      .relate("group", "average");

    selUsers.groupBy("name").count().select("group as users", "count");

    selSuccess = selSuccess.contents;
    selDuration = selDuration.contents;
    selUsers = selUsers.contents;
    selScaled = selScaled.contents;
    for (let i = 0, scaled, dur; i < selUsers.length; i++) {
      users[i] = "User " + (i + 1);
      scaled = [];
      dur = [];
      for (let k = 0, c, d, o; k < selScaled.length; k++) {
        o = selScaled[k].group;
        objects[k] = o.substring(
          o.indexOf("h5pcid_"),
          o.indexOf("/", o.indexOf("h5pcid_"))
        );
        dur[k] = 0;
        scaled[k] = 0;
        c = 0;
        d = 0;

        if (selSuccess[k].hasOwnProperty(selUsers[i].users)) {
          if (selSuccess[k][selUsers[i].users] > 0) success.true++;
          else success.false++;
        }
        if (selScaled[k].hasOwnProperty(selUsers[i].users)) {
          c++;
          scaled[k] += Number(selScaled[k][selUsers[i].users]);
        }
        if (selDuration[k].hasOwnProperty(selUsers[i].users)) {
          d++;
          dur[k] += moment
            .duration(selDuration[k][selUsers[i].users], "milliseconds")
            .as("minutes");
        }
        if (dur[k] > 0) {
          scaled[k] = (scaled[k] / c).toFixed(1);
          dur[k] = (dur[k] / d).toFixed(1);
        }
      }

      var rcolor_ = rcolor();
      series.push({
        name: users[i],
        type: "bar",
        emphasis: {
          focus: "series"
        },
        itemStyle: {
          color: rcolor_
        },
        //stack: Object.keys(freqUsers)[j],
        //barWidth: "33%",
        data: scaled
      });
      series.push({
        name: users[i] + " d",
        type: "bar",
        emphasis: {
          focus: "series"
        },
        itemStyle: {
          borderWidth: 1,
          borderColor: "white",
          borderType: "solid",
          color: rcolor_ + "66"
        },
        //stack: Object.keys(freqUsers)[j],
        barGap: 0,
        barWidth: "5%",
        data: dur
      });
    }

    for (let i = 0; i < Object.keys(success).length; i++) {
      if (Object.keys(success)[i] === "false") color = "#E74E54";
      else color = "#80C462";
      pieData.push({
        value: Object.values(success)[i],
        name: Object.keys(success)[i],
        itemStyle: {
          color: color
        }
      });
    }
    series.push({
      name: "Objects",
      type: "pie",
      radius: [0, 70],
      center: ["87%", "27%"],
      itemStyle: {
        borderRadius: 5
      },
      label: {
        show: true
      },
      emphasis: {
        label: {
          show: true
        }
      },
      data: pieData
    });

    option = {
      title: {
        text: "Wer hat welche H5P-Interaktionen mit welchem Erfolg bearbeitet?",
        left: ""
      },
      toolbox: {
        show: true,
        feature: {
          dataZoom: {
            yAxisIndex: "false"
          },
          dataView: {
            readOnly: false
          },
          magicType: {
            type: ["bar", "stack"]
          },
          restore: {},
          saveAsImage: {}
        }
      },
      dataZoom: [
        {
          type: "slider",
          xAxisIndex: 0,
          filterMode: "none"
        },
        {
          type: "inside",
          xAxisIndex: 0,
          filterMode: "none"
        }
      ],
      legend: {
        orient: "vertical",
        left: "75%",
        top: "48%",
        type: "scroll"
      },
      tooltip: {
        //trigger: "axis",
        axisPointer: {
          type: "shadow"
        }
      },
      grid: {
        left: "3%",
        right: "27%",
        bottom: "3%",
        top: "12%",
        containLabel: true
      },
      xAxis: [
        {
          type: "category",
          data: objects,
          //minInterval: 1,
          axisTick: {
            alignWithLabel: true
          }
        }
      ],
      yAxis: [
        {
          type: "value",
          minInterval: ""
        }
      ],
      series: series
    };
    if (option && typeof option === "object") {
      myChart.setOption(option);
    }
    document.querySelector(".spinner-border").style.display = "none";
    setTimeout(() => {
      let resizeEvent = new Event("resize");
      window.dispatchEvent(resizeEvent);
    }, 0);
    window.addEventListener("resize", myChart.resize);

    var zoomSize = 5,
      click = true,
      sv,
      ev;
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
    myChart.on("click", function (params) {
      if (params.componentSubType === "bar") {
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
          endValue: ev
        });
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
