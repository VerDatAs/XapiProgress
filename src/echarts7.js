function echarts7_(event, container, page, echartQuery) {
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
  const st = statements;
  let selection = new ADL.Collection(st);
  selection
    .where(
      'actor.account != "undefined" \
      and actor.account.name != "6@b4b4f485-9c96-4593-bb0b-9674d0840834.ilias" \
      and (verb.id = "http://adlnet.gov/expapi/verbs/completed" \
      or verb.id = "http://adlnet.gov/expapi/verbs/answered" \
      or verb.id = "http://adlnet.gov/expapi/verbs/experienced" \
      or verb.id = "http://adlnet.gov/expapi/verbs/failed")'
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
            data[i]["duration"] = moment
              .duration(data[i].result.duration)
              .asMinutes();
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
          data[i]["month"] = moment(data[i].timestamp).format("YYYY-MM");
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
  //console.log(selection.contents);
  return selection.contents;
}

// function: prepare option object
function echartSetup(container, data_, temp) {
  if (document.getElementById(container))
    container = document.getElementById(container);

  let myChart = echarts.init(container),
    option,
    options,
    rcolor_ = [],
    series = [],
    timelineDurations = [],
    pieData = [],
    selDurations = [],
    selCourseDurations = new ADL.Collection(data_),
    selUsers = new ADL.Collection(data_),
    timelineData = [],
    timelineSeries = [];

  selCourseDurations
    .groupBy("month")
    .groupBy("name")
    .groupBy("day")
    .sum("courseduration", 2);
  selCourseDurations = selCourseDurations.contents;
  //console.log("selCourseDurations");
  //console.log(selCourseDurations);

  selUsers
    .groupBy("name")
    .count()
    .sum("courseduration")
    .select("group as user, count, sum as totalDuration");
  selUsers = selUsers.contents;
  //console.log("selUsers");
  //console.log(selUsers);

  function generateDateArray(startDate, numberOfDays, data, user) {
    let dates = [],
      ddates = [],
      tdates = [],
      retDates = [],
      mdates = 0,
      currentDay = moment(startDate).startOf("month");
    for (let n = 0; n < numberOfDays; n++) {
      dates.push(currentDay.format("YYYY-MM-DD"));
      ddates.push(null);
      for (let d = 0; d < data.length; d++) {
        for (let d_ = 0; d_ < data[d].data.length; d_++) {
          if (
            moment(data[d].data[d_].group).format("YYYY-MM-DD") ===
              currentDay.format("YYYY-MM-DD") &&
            data[d].group === user
          ) {
            ddates[n] = data[d].data[d_].sum.toFixed(1);
            mdates += data[d].data[d_].sum;
          }
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
    for (let m = 0; m < selCourseDurations.length; m++) {
      retDates = generateDateArray(
        selCourseDurations[m].group,
        moment(selCourseDurations[m].group).daysInMonth(),
        selCourseDurations[m].data,
        selUsers[u].user
      );
      selDurations[u][m] = retDates.tdates;
      retDates.mdates = {
        value: retDates.mdates,
        name: "User " + (u + 1),
        itemStyle: { color: rcolor_[u] },
      };
      timelineDurations[u][m] = retDates.mdates;
      if (u < 1) {
        timelineData.push(selCourseDurations[m].group);
        timelineSeries.push({
          series: [],
        });
      }
      timelineSeries[m].series.push({
        name: "User " + (u + 1),
        type: "bar",
        emphasis: {
          focus: "series",
        },
        barGap: 0,
        itemStyle: {
          color: rcolor_[u],
        },
        label: {
          show: true,
          position: "top",
          distance: "-20",
          align: "center",
          verticalAlign: "middle",
          fontSize: 14,
          color: "white",
          rotate: "90",
        },
        stack: "",
        data: selDurations[u][m],
      });
    }
  }

  for (let m = 0; m < selCourseDurations.length; m++) {
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
      data: pieData[m],
    });
  }


  options = timelineSeries;

  for (let u = 0; u < selUsers.length; u++) {
    series.push({
      name: "User " + (u + 1),
      type: "bar",
      emphasis: {
        focus: "series",
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
        rotate: "90",
      },
      stack: "",
    });
    pieData.push(timelineDurations[u][0]);
  }
  series.push({
    name: "Bearbeitungsdauer",
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

  option = {
    baseOption: {
      title: {
        text: "Wann und wie lange waren einzelne Nutzer im Lernmodul?",
        left: "3%",
      },
      timeline: {
        data: timelineData,
        bottom: 0,
        left: "5%",
        right: "27%",
        axisType: "category",
        replaceMerge: ["series"],
        controlStyle: {
          showPlayBtn: false,
          showPrevBtn: false,
          showNextBtn: false,
        },
      },
      toolbox: {
        show: true,
        feature: {
          dataZoom: {
            yAxisIndex: "false",
          },
          dataView: {
            readOnly: false,
          },
          magicType: {
            type: ["line", "bar", "stack"],
          },
          restore: {},
          saveAsImage: {},
        },
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
        type: "scroll",
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
        bottom: "15%",
        top: "12%",
        containLabel: true,
      },
      xAxis: [
        {
          type: "category",
          minInterval: 1,
          axisTick: {
            alignWithLabel: true,
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
                "rgba(0,0,0,0.05)",
              ],
            },
          },
        },
      ],
      yAxis: [
        {
          type: "value",
          minInterval: 1,
        },
      ],
      series: series,
    },
    options: options,
  };

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
    this.setOption({
      xAxis: [
        {
          type: "category",
          minInterval: 1,
          axisTick: {
            alignWithLabel: true,
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
                "rgba(0,0,0,0.05)",
              ],
            },
          },
        },
      ],
    });
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
