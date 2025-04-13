function echarts62_(event, container, data_, echartQuery) {
  container = document.getElementById(container);
  let myChart = echarts.init(container),
    option,
    selUsers = new ADL.Collection(data_),
    selCourseDurations = new ADL.Collection(data_),
    selMonths = new ADL.Collection(data_),
    calendars = [],
    series = [];

  selUsers
    .groupBy("day")
    .groupBy("name")
    .count()
    .select("group as day, count as users");
  //selUsers = selUsers.contents;
  //console.log("selUsers");
  //console.log(selUsers);

  selMonths.groupBy("month").select("group as month");
  selMonths = selMonths.contents;
  //console.log("selMonths");
  //console.log(selMonths);

  selCourseDurations
    .groupBy("name")
    .groupBy("day")
    .groupBy("courseduration")
    .max("group", 1)
    .exec(function (data) {
      for (let i = 0; i < data.length; i++) {
        for (let j = 0; j < data[i].data.length; j++) {
          if (j < 1)
            data[i].data[j].dayduration = data[i].data[j].max.toFixed(1);
          else
            data[i].data[j].dayduration = (
              data[i].data[j].max - data[i].data[j - 1].max
            ).toFixed(1);
        }
      }
      return data;
    });
  selCourseDurations = selCourseDurations.contents;
  //console.log("selCourseDurations");
  //console.log(selCourseDurations);

  const cellSize = [60, 60],
    pieRadius = 20;

  function getVirtualData(su, sd, m) {
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

    let day = [];
    for (let d = start; d < end; d += dayTime) {
      day[d] = [];
      for (let user = 0; user < sd.length; user++) {
        for (let dur = 0; dur < sd[user].data.length; dur++) {
          if (
            +echarts.time.parse(sd[user].data[dur].group) === d &&
            sd[user].data[dur].dayduration.length > 0
          ) {
            day[d] = [
              ...day[d],
              {
                ["name"]: "User " + (user + 1),
                ["value"]: Number(sd[user].data[dur].dayduration),
              },
            ];
          }
        }
      }
      data.push([echarts.time.format(d, "{yyyy}-{MM}-{dd}", false), day[d]]);
    }
    return data;
  }

  for (let i = 0; i < selMonths.length; i++) {
    let scatterData = getVirtualData(selUsers, selCourseDurations, i),
      pieSeries = scatterData.map(function (item, index) {
        return {
          type: "pie",
          id: "pie-" + index + i,
          center: item[0],
          radius: pieRadius,
          coordinateSystem: "calendar",
          calendarIndex: i,
          label: {
            formatter: "{c}",
            position: "inside",
          },
          data: item[1],
        };
      });
    calendars.push({
      top: 110,
      orient: "vertical",
      cellSize: cellSize,
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
        id: "label" + i,
        type: "scatter",
        coordinateSystem: "calendar",
        symbolSize: 0,
        label: {
          show: false,
          formatter: function (params) {
            return echarts.time.format(params.value[0], "{dd}", false);
          },
          offset: [-cellSize[0] / 2 + 10, -cellSize[1] / 2 + 10],
          fontSize: 14,
        },
        data: scatterData,
      },
      ...pieSeries
    );
  }

  option = {
    title: {
      //top: 30,
      left: 78,
      text: "Bearbeitungsdauer pro Tag",
    },
    //position: "top",
    legend: {
      bottom: 20,
    },
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
}
