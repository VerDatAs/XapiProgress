// function: Wann und wie lange waren einzelne Nutzer im Lernmodul?

function echarts5_(event, container, page, echartQuery) {
  let data = getStatementsSelection("terminated", page, echartQuery);
  if (typeof data !== "undefined" && data.length > 0)
    echartSetup(container, data, echartQuery);
  else {
    userAlerts("nodatamodal");
    return;
  }
}

// function: get duration of terminated statements
function getStatementsSelection(verb, page, echartQuery) {
  let stmtsQ = sessionStorage.getItem("statements"),
    since = "",
    until = "",
    stmtsQ_,
    qStored,
    data = [];
  // check if previuos data of echarts5 in sessiostorage and get data if applicable
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
  let selection = statements; /*bm.getStatementsBase(
    verb,
    "", //agent
    "", //registration
    "", //sessionid
    since,
    until,
    false, //relatedactivities
    true, //relatedagents
    "ids", //format
    cmi5Controller.activityId,
    "", //page
    true, //more
    "", //extensionsActivityId
    echartQuery //query
  );*/
console.log(selection);
  selection = new ADL.Collection(selection);
  selection
    .where("actor.account !== 'undefined'")
    .orderBy("timestamp")
    .exec(function (data) {
      for (var i = 0; i < data.length; i++) {
        data[i]["name"] = data[i].actor.account.name;
        data[i]["object"] = data[i].object.id;
        data[i]["duration"] = moment
          .duration(data[i].result.duration)
          .asMinutes();
        data[i]["month"] = moment(data[i].timestamp).format("YYYY-MM");
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
      return selection;
    });

  // push previuos data in sessiostorage of echarts4 to data object if applicable
  if (qStored) selection.contents.push(...stmtsQ_);

  // assign sorted data to cmi5Controller[echarts4] object
  if (selection.contents.length > 0) {
    if (stmtsQ.length > 0) {
      for (let i = 0; i < stmtsQ.length; i++) {
        if (Object.keys(stmtsQ[i]).includes(echartQuery))
          stmtsQ[i] = { [echartQuery]: selection.contents };
      }
    } else stmtsQ.push({ [echartQuery]: selection.contents });
    // update data in sessionstorage
    sessionStorage.setItem("statements", JSON.stringify(stmtsQ));
  }
  selection.orderBy("timestamp");
  return selection.contents;
}

// function: draw echart
function echartSetup(container, data_, echartQuery) {
  if (document.getElementById(container))
    container = document.getElementById(container);
  if (sessionStorage.getItem("cmi5No") == "false") {
    let myChart = echarts.init(container),
      option,
      options,
      timeline,
      rcolor_ = [],
      series = [],
      timelineDurations = [],
      pieData = [],
      selDurations = [],
      selDuration = new ADL.Collection(data_),
      selUsers = new ADL.Collection(data_),
      t1 = moment(selDuration.contents[0].timestamp)
        .startOf("week")
        .isoWeekday(1),
      t2 = selDuration.contents[selDuration.contents.length - 1].timestamp,
      timelineData = [],
      timelineSeries = [];

    selDuration
      .groupBy("timestamp", [
        moment(t1).startOf("day").toISOString(),
        t2,
        86400000
      ])
      .groupBy("name")
      .sum("duration", 1)
      .select(
        "groupStart as date, data[0].group as user, data[0].sum as duration, data[0].data[0].month as month"
      )
      .groupBy("month");
    selUsers
      .groupBy("name")
      .count()
      .sum("duration")
      .select("group as user, count, sum as totalDuration");
    selDuration = selDuration.contents;
    selUsers = selUsers.contents;
    function generateDateArray(startDate, numberOfDays, data, user) {
      let dates = [],
        ddates = [],
        tdates = [],
        retDates = [],
        mdates = 0,
        currentDay = moment(startDate);
      for (let n = 0; n < numberOfDays; n++) {
        dates.push(currentDay.format("YYYY-MM-DD"));
        ddates.push(0);
        for (let d = 0; d < data.length; d++) {
          if (
            moment(data[d].date).format("YYYY-MM-DD") ===
              currentDay.format("YYYY-MM-DD") &&
            data[d].user === user
          ) {
            ddates[n] = data[d].duration.toFixed(1);
            mdates += data[d].duration;
          }
        }
        currentDay.add(1, "day");
        tdates.push([dates[n], ddates[n]]);
      }
      retDates.tdates = tdates;
      retDates.mdates = mdates.toFixed(1);
      return retDates;
    }
    for (let u = 0; u < selUsers.length; u++) {
      rcolor_[u] = rcolor();
      selDurations[u] = [];
      timelineDurations[u] = [];
      for (let m = 0; m < selDuration.length; m++) {
        retDates = generateDateArray(
          selDuration[m].group,
          moment(selDuration[m].group).daysInMonth(),
          selDuration[m].data,
          selUsers[u].user
        );
        selDurations[u][m] = retDates.tdates;
        retDates.mdates = {
          value: retDates.mdates,
          name: "User " + (u + 1),
          itemStyle: { color: rcolor_[u] }
        };
        timelineDurations[u][m] = retDates.mdates;
        if (u < 1) {
          timelineData.push(selDuration[m].group);
          timelineSeries.push({
            series: []
          });
        }
        timelineSeries[m].series.push({
          name: "User " + (u + 1),
          type: "bar",
          emphasis: {
            focus: "series"
          },
          barGap: 0,
          itemStyle: {
            color: rcolor_[u]
          },
          label: {
            show: true,
            position: "top",
            distance: "-20",
            align: "center",
            verticalAlign: "middle",
            fontSize: 14,
            color: "white",
            rotate: "90"
          },
          stack: "",
          data: selDurations[u][m]
        });
      }
    }

    for (let m = 0; m < selDuration.length; m++) {
      pieData[m] = [];
      for (let u = 0; u < selUsers.length; u++) {
        console.log(timelineDurations[u][m].value);
        if (Number(timelineDurations[u][m].value) > 0)
          pieData[m].push(timelineDurations[u][m]);
      }
      timelineSeries[m].series.push({
        name: "Bearbeitungsdauer",
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
        data: pieData[m]
      });
    }

    timeline = {
      data: timelineData,
      bottom: 0,
      left: "5%",
      right: "27%",
      axisType: "category",
      replaceMerge: ["series"],
      controlStyle: {
        showPlayBtn: false,
        showPrevBtn: false,
        showNextBtn: false
      }
      //padding: [00, 0, 0, 0]
    };
    options = timelineSeries;

    for (let u = 0; u < selUsers.length; u++) {
      series.push({
        name: "User " + (u + 1),
        type: "bar",
        emphasis: {
          focus: "series"
        },
        barGap: 0,
        label: {
          show: true,
          position: "top",
          distance: "-20",
          align: "center",
          verticalAlign: "middle",
          fontSize: 14,
          color: "white",
          rotate: "90"
        },
        stack: ""
      });
      pieData.push(timelineDurations[u][0]);
      /*pieData.push({
        value: selUsers[u].totalDuration.toFixed(0),
        name: "User " + (u + 1),
        itemStyle: {
          //color: color
        }
      });*/
    }
    series.push({
      name: "Bearbeitungsdauer",
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
      baseOption: {
        title: {
          text: "Wann und wie lange waren einzelne Nutzer im Lernmodul?",
          left: "3%"
        },
        timeline: timeline,
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
              type: ["line", "bar", "stack"]
            },
            restore: {},
            saveAsImage: {}
          }
        },
        /*dataZoom: [
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
        ],*/
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
          bottom: "15%",
          top: "12%",
          containLabel: true
        },
        xAxis: [
          {
            type: "category",
            minInterval: 1,
            axisTick: {
              alignWithLabel: true
            },
            splitArea: {
              interval: 0,
              show: true,
              areaStyle: {
                color: [
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0.05)",
                  "rgba(0,0,0,0.05)"
                ]
              }
            }
          }
        ],
        yAxis: [
          {
            type: "value",
            minInterval: 1
          }
        ],
        series: series
      },
      options: options
    };

    if (option && typeof option === "object") {
      myChart.setOption(option);
    }
    document.querySelector(".spinner-border").style.display = "none";
    window.addEventListener("resize", myChart.resize);

    var zoomSize = 8,
      click = true,
      sv,
      ev;
    myChart.on("click", function (params) {
      this.setOption({
        xAxis: [
          {
            type: "category",
            minInterval: 1,
            axisTick: {
              alignWithLabel: true
            },
            splitArea: {
              interval: 0,
              show: true,
              areaStyle: {
                color: [
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0)",
                  "rgba(0,0,0,0.05)",
                  "rgba(0,0,0,0.05)"
                ]
              }
            }
          }
        ]
      });

      if (params.componentSubType === "bar") {
        if (click) {
          click = false;
          sv = params.value[0] - zoomSize / 2;
          ev = params.value[0] + zoomSize / 2;
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
}
function rcolor() {
  let maxVal = 0xffffff; // 16777215
  let randomNumber = Math.random() * maxVal;
  randomNumber = Math.floor(randomNumber);
  randomNumber = randomNumber.toString(16);
  let randColor = randomNumber.padStart(6, 0);
  return "#" + randColor.toUpperCase();
}
