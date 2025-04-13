function echarts61_(event, container, data_, echartQuery) {
  container = document.getElementById(container);
  let myChart = echarts.init(container),
    option,
    selUsers = new ADL.Collection(data_),
    selMonths = new ADL.Collection(data_);

  selUsers
    .groupBy("day")
    .groupBy("name")
    .count()
    .select("group as day, count as users");
  //console.log("selUsers");
  //console.log(selUsers);

  selMonths.groupBy("month").select("group as month");
  selMonths = selMonths.contents;
  //console.log("selMonths");
  //console.log(selMonths);

  function getVirtualData(su, m) {
    let su_ = [],
      startOfMonth = moment(su.contents[0].day)
        .startOf("month")
        .add(m, "months"),
      endOfMonth = moment(su.contents[0].day)
        .startOf("month")
        .add(m + 1, "months");
    su.exec(function (data) {
      for (var i = 0; i < data.length; i++) {
        if (
          moment(data[i].day).isBefore(endOfMonth) &&
          moment(data[i].day).isAfter(startOfMonth)
        )
          su_.push(data[i]);
      }
    });
    su = su_;
    const start = +echarts.time.parse(moment(su[0].day).startOf("month")),
      end = +echarts.time.parse(moment(start).endOf("month")),
      dayTime = 3600 * 24 * 1000,
      data = [];
    let users = 0;
    for (let d = start; d < end; d += dayTime) {
      for (let i = 0; i < su.length; i++) {
        if (+echarts.time.parse(su[i].day) === d) users = su[i].users;
      }
      data.push([echarts.time.format(d, "{yyyy}-{MM}-{dd}", false), users]);
      users = 0;
    }
    return data;
  }
  let calendars = [], series = [];
  for (let i = 0; i < selMonths.length; i++) {
    calendars.push({
      top: 110,
      orient: "vertical",
      cellSize: 60,
      yearLabel: {
        margin: 50,
        show: true,
        fontSize: 30,
      },
      dayLabel: {
        margin: 20,
        firstDay: 1,
        nameMap: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
      },
      monthLabel: {
        margin: 20,
        show: true,
      },
      cellSize: 60,
      left: 80 + i * 600,
      range: selMonths[i].month,
    });
    series.push(
      {
        type: "effectScatter",
        coordinateSystem: "calendar",
        calendarIndex: i,
        label: {
          show: true,
          formatter: function (params) {
            return "\n\n\n" + (params.value[1] || "");
          },
          fontSize: 12,
          color: "#000",
          //fontWeight: 600,
          //color: "#000",
        },
        symbolSize: function (val) {
          return val[1] * 5;
        },
        color: "green",
        data: getVirtualData(selUsers, i),
      }
    );
  }
  option = {
    title: {
      //top: 30,
      left: 78,
      text: "Teilnehmer pro Tag",
    },
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
              text: 'Weiter zur Monatsübersicht "Bearbeitungsdauer pro Tag"',
              font: "14px Calibri",
            },
          },
        ],
      },
    ],
    tooltip: {
      //position: "top",
    },
    calendar: calendars,
    series: series,
  };

  if (option && typeof option === "object") {
    myChart.setOption(option);
  }

  setTimeout(() => {
    let resizeEvent = new Event("resize");
    window.dispatchEvent(resizeEvent);
  }, 0);
  window.addEventListener("resize", myChart.resize);

  myChart.on("click", function (params) {
    //console.log(params);
    if (params.componentType === "graphic") {
      let c = document.getElementById("container3");
      if (c.classList.contains("hide")) {
        if (!c.innerHTML) {
          loadScript("echarts62.js", function () {
            echarts62_("", "container3", data_);
          });
        }
        c.classList.remove("hide");
        c.classList.add("show");
      } else {
        c.classList.remove("show");
        c.classList.add("hide");
      }
    }
  });
}
