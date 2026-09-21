List<Notice> noticeFromJson(
        List<dynamic> noticeJson) =>
    List<Notice>.from(noticeJson.map((noticeJson) =>
        Notice.fromJson(noticeJson)));

class Notice {
  int? id;
  String? title;
  String? description;
  String? nepaliTitle;
  String? nepaliDescription;
  String? link;
  String? updatedAt;

  Notice(
      {this.id,
      this.title,
      this.description,
      this.nepaliTitle,
      this.nepaliDescription,
      this.link,
      this.updatedAt});

  Notice.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = json['title'];
    description = json['description'];
    nepaliTitle = json['nepaliTitle'];
    nepaliDescription = json['nepaliDescription'];
    link = json['link'];
    updatedAt = json['updated_at'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['title'] = title;
    data['description'] = description;
    data['nepaliTitle'] = nepaliTitle;
    data['nepaliDescription'] = nepaliDescription;
    data['link'] = link;
    data['updated_at'] = updatedAt;
    return data;
  }
}