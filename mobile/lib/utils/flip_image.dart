import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'dart:math';

class FlippableImage extends StatefulWidget {
  final String imageUrl;
  final String name;

  const FlippableImage({
    Key? key,
    required this.imageUrl,
    required this.name,
  }) : super(key: key);

  @override
  // ignore: library_private_types_in_public_api
  _FlippableImageState createState() => _FlippableImageState();
}

class _FlippableImageState extends State<FlippableImage> {
  bool isFlipped = true;
  double angle = 0;

  void _flip() {
    setState(() {
      angle = (angle + pi) % (2 * pi);
    });
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: _flip,
      child: TweenAnimationBuilder(
        tween: Tween<double>(begin: 0, end: angle),
        duration: const Duration(seconds: 1),
        builder: (BuildContext context, double val, __) {
          // Changing the isBack val to change the content of the card
          if (val >= (pi / 2)) {
            isFlipped = false;
          } else {
            isFlipped = true;
          }
          return (Transform(
            // Making the card flip by  center
            alignment: Alignment.center,
            transform: Matrix4.identity()
              ..setEntry(3, 2, 0.001)
              ..rotateY(val),
            child: SizedBox(
              width: 120,
              height: 120,
              child: isFlipped
              ? CachedNetworkImage(
                placeholder: 
                  (BuildContext context, String url) => 
                  const Center(child: CircularProgressIndicator()),
                  imageUrl: widget.imageUrl,
                height: 120,
                width: 120,
                errorWidget: (context, url, error) {
                  return const Center(child: Text("Error Loading Image", style: TextStyle(color: Colors.white),),);
                },
              )
              : Transform(
                alignment: Alignment.center,
                transform: Matrix4.identity()
                  ..rotateY(pi), // it will flip horizontally the container
                child: Container(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(10.0),
                  ),
                  child: Center(
                    child: Text(
                      widget.name,style: const TextStyle(fontSize: 25),
                    ), 
                  ),
                ),
              ),
            ), 
          ));
        }
      )
    );
  }
}
